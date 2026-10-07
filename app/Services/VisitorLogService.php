<?php

namespace App\Services;

use App\Actions\PreregisterVisitor\GetOnePreregisterVisitorAction;
use App\Actions\Visitor\ExportVisitorLogAction;
use App\Actions\Visitor\GetOneVisitorAction;
use App\Actions\Visitor\UpdateVisitorLogAction;
use App\Enums\Residence\Features;
use App\Enums\Visitor\ArrivalType;
use App\Enums\Visitor\VisitingArrangementStatus;
use App\Exceptions\GeneralException;
use App\Helpers\VisitorHelper;
use App\Http\Requests\Visitor\CreateUpdateVisitorRequest;
use App\Http\Requests\Visitor\GetVisitorRequest;
use App\Http\Requests\Visitor\GetVisitorStatisticRequest;
use App\Http\Requests\Visitor\StoreVisitorRequest;
use App\Http\Requests\Visitor\UpdateVisitorRequest;
use App\Jobs\ImageProcessing\ProcessVisitorImage;
use App\Jobs\Visitor\SendVisitorArrivedNotifications;
use App\Models\BlacklistedVisitor;
use App\Models\PreregisterVisitor;
use App\Models\ResidenceFeature;
use App\Models\VisitingArrangement;
use App\Models\Visitor;
use App\Models\VisitorApiResponse;
use App\Models\VisitorLog;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use stdClass;
use Throwable;

class VisitorLogService
{
    public function getStatistic(GetVisitorStatisticRequest $request)
    {
        $date = $request->date ?? now()->toDateString();
        $residenceId = $request->input('residence_id');

        $cache = cache()->store('redis');
        $cacheKey = "visitor_stats:{$residenceId}:{$date}";

        return $cache->remember($cacheKey, 30, function () use ($date, $residenceId) {
            $dayStart = $date.' 00:00:00';
            $dayEnd = $date.' 23:59:59';
            $nextDayStart = Carbon::parse($date)->addDay()->toDateString().' 00:00:00';

            $stats = DB::table('visitor_logs')
                ->where('residence_id', $residenceId)
                ->where(function ($q) {
                    $q->where('is_allowed', 1)->orWhereNull('is_allowed');
                })
                ->where(function ($q) use ($dayStart, $nextDayStart) {
                    $q->whereBetween('arrival_time', [$dayStart, $nextDayStart])
                        ->orWhereBetween('leave_time', [$dayStart, $nextDayStart]);
                })
                ->selectRaw('COUNT(CASE WHEN arrival_time >= ? AND arrival_time < ? THEN 1 END) as total_in', [$dayStart, $nextDayStart])
                ->selectRaw('COUNT(CASE WHEN leave_time >= ? AND leave_time < ? THEN 1 END) as total_out', [$dayStart, $nextDayStart])
                ->selectRaw('COUNT(CASE WHEN arrival_time >= ? AND arrival_time < ? AND leave_time IS NULL THEN 1 END) as remaining', [$dayStart, $nextDayStart])
                ->selectRaw('COUNT(CASE WHEN arrival_time >= ? AND arrival_time < ? AND (leave_time IS NULL OR leave_time >= ?) THEN 1 END) as overnight', [$dayStart, $nextDayStart, $nextDayStart])
                ->first();

            // Parking fees — separate query since it needs JOIN to visitor_parkings
            $parkingFees = DB::table('visitor_logs as vl')
                ->join('visitor_parkings as vp', 'vl.id', '=', 'vp.visitor_log_id')
                ->where('vl.residence_id', $residenceId)
                ->where(function ($q) {
                    $q->where('vl.is_allowed', 1)->orWhereNull('vl.is_allowed');
                })
                ->whereBetween('vl.leave_time', [$dayStart, $nextDayStart])
                ->selectRaw('COALESCE(SUM(vp.amount_to_pay), 0) as total_parking_fees_collected')
                ->first();

            return [[
                'total_in' => (int) ($stats->total_in ?? 0),
                'total_out' => (int) ($stats->total_out ?? 0),
                'remaining' => (int) ($stats->remaining ?? 0),
                'overnight' => (int) ($stats->overnight ?? 0),
                'total_parking_fees_collected' => (float) ($parkingFees->total_parking_fees_collected ?? 0),
            ]];
        });
    }

    public function index(GetVisitorRequest $request)
    {
        $visitorLog = VisitorLog::query();

        $includeRelations = $request->boolean('include_relations', true);
        $includeArrangements = $request->boolean('include_arrangements', true);

        if ($includeRelations) {
            $relations = [
                'media',
                'visitor:id,name,id_type,id_number,contact_no',
                'visitorCard:id,visitor_card_no',
            ];

            if ($includeArrangements) {
                $relations = array_merge($relations, [
                    'visitingArrangements:id,visitor_log_id,unit_id,user_id,residence_id,status,estamp_by,estamp_by_type,feedback_remark',
                    'visitingArrangements.unit:id,unit_number,block',
                    'visitingArrangements.unit.media',
                    'visitingArrangements.user:id,name',
                    'visitingArrangements.user.media',
                    'visitingArrangements.residence:id',
                    'visitingArrangements.residence.media',
                    'visitingArrangements.residence.visitorSetting',
                    'visitingArrangements.residence.visitorSetting.media',
                ]);
            }

            $visitorLog->with($relations);
        }

        $visitorLog->when($request->id, fn ($q) => $q->where('id', $request->id))
            ->when($request->visitor_id, fn ($q) => $q->where('visitor_id', $request->visitor_id))
            ->when($request->visitor_card_id, fn ($q) => $q->where('visitor_card_id', $request->visitor_card_id))
            ->when($request->visitor_code ?? $request->code, fn ($q) => $q->where('visitor_code', $request->visitor_code ?? $request->code))
            ->when($request->residence_id, fn ($q) => $q->where('residence_id', $request->residence_id))
            ->when($request->vehicle_plate_no, fn ($q) => $q->where('vehicle_plate_no', 'like', "%{$request->vehicle_plate_no}%")); // Note: LIKE '%term%' cannot use index

        $visitorLog->when($request->name_vehicle_no, function ($q) use ($request) {
            $searchTerm = "%{$request->name_vehicle_no}%";

            $q->where(function ($query) use ($searchTerm) {
                $query->whereHas('visitor', function ($visitorQuery) use ($searchTerm) {
                    $visitorQuery->where('name', 'like', $searchTerm);
                })
                    ->orWhere('vehicle_plate_no', 'like', $searchTerm);
            });
        });

        $visitorLog->when($request->name, fn ($q) => $q->whereHas('visitor', fn ($query) => $query->where('name', $request->name)));

        $visitorLog->when(isset($request->status), function ($q) use ($request) {
            return match ((int) $request->status) {
                0 => $q->whereNull(['leave_time', 'arrival_time']), // PENDING
                1 => $q->whereNull('leave_time')->whereNotNull('arrival_time'), // IN - can use visitor_code_leave_time index
                2 => $q->whereNotNull(['leave_time', 'arrival_time']), // OUT
                default => $q,
            };
        });

        if ($request->boolean('is_overnight') && $request->visit_at_range) {
            $arrivalDate = Carbon::parse($request->visit_at_range); // Expected format: '2025-03-07'

            $visitorLog->whereBetween('arrival_time', [
                $arrivalDate->clone()->startOfDay(),
                $arrivalDate->clone()->endOfDay(),
            ])
                ->where(function ($q) use ($arrivalDate) {
                    $q->whereNull('leave_time') // Still inside, no leave time
                        ->orWhere('leave_time', '>', $arrivalDate->clone()->endOfDay()); // Left after midnight
                });
        } elseif ($request->filled('visit_at_range') && ! $request->boolean('is_overnight')) {
            [$start_date, $end_date] = $this->parseDateRange($request->visit_at_range);
            $visitorLog->whereBetween('arrival_time', [$start_date, $end_date]);
        }

        if ($request->filled('leave_at_range')) {
            [$start_date, $end_date] = $this->parseDateRange($request->leave_at_range);
            $visitorLog->whereBetween('leave_time', [$start_date, $end_date]);
        }

        return $visitorLog->latest('id')
            ->paginate($request->item_per_page ?? 10);
    }

    // Helper method to parse date ranges
    private function parseDateRange(?string $dateRange): array
    {
        $dateRange = trim((string) $dateRange);
        if ($dateRange === '') {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Date range is required');
        }

        $dates = array_map('trim', explode('-', $dateRange));
        if (count($dates) !== 2 || $dates[0] === '' || $dates[1] === '') {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Invalid date range format');
        }

        [$startDate, $endDate] = $dates;

        try {
            return [
                Carbon::parse($startDate)->startOfDay()->toDateTimeString(),
                Carbon::parse($endDate)->endOfDay()->toDateTimeString(),
            ];
        } catch (Exception $e) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Invalid date format');
        }
    }

    public function create(StoreVisitorRequest $request)
    {
        $cache = cache()->store('redis');
        $cacheTtlSeconds = 120;

        if (! empty($request->vehicle_info) && is_string($request->vehicle_info)) {
            $decoded = json_decode($request->vehicle_info, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $request->merge(['vehicle_info' => $decoded]);
            }
        }

        $preregisterData = null;
        $preregisterCacheKey = null;
        if ($request->is_pre_register || $request->visitor_code) {
            $preregisterCacheKey = "preregister:visitor_code:{$request->visitor_code}";
            $preregisterData = $cache->remember($preregisterCacheKey, $cacheTtlSeconds, function () use ($request) {
                return PreregisterVisitor::query()
                    ->select([
                        'id',
                        'visitor_code',
                        'unit_id',
                        'user_id',
                        'visitor_purpose',
                        'vehicle_plate_no',
                        'arrival_type',
                        'vehicle_type',
                        'company_name',
                        'passenger_count',
                        'remark',
                        'vehicle_info',
                        'validity_start_date',
                        'is_multiple_entry',
                        'is_qr_code_expired',
                        'visitor_id',
                    ])
                    ->with([
                        'unit:id,residence_id',
                        'unit.residence:id',
                        'visitor:id,name,contact_no,id_type,id_number',
                    ])
                    ->where('visitor_code', $request->visitor_code)
                    ->firstOrFail()
                    ->toArray();
            });

            if (data_get($preregisterData, 'is_qr_code_expired')) {
                return response()->json([
                    'message' => __('api-response.error.qr_expired'),
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            if (now()->toDateTimeString() < data_get($preregisterData, 'validity_start_date')) {
                return response()->json([
                    'message' => __('api-response.error.qr_valid_from', ['date' => data_get($preregisterData, 'validity_start_date')])
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            if (! data_get($preregisterData, 'is_multiple_entry')) {
                $visitorCodeKey = "visitor_code:active:{$request->visitor_code}";
                $isActive = $cache->remember($visitorCodeKey, $cacheTtlSeconds, function () use ($request) {
                    return VisitorLog::where('visitor_code', $request->visitor_code)->exists();
                });

                if ($isActive) {
                    return response()->json([
                        'message' => 'The visitor is already in!',
                    ], JsonResponse::HTTP_BAD_REQUEST);        
                }
            }

            if (! isset($request->is_pre_register)) {
                $request->merge([
                    'is_pre_register' => true,
                    'vehicle_info' => data_get($preregisterData, 'vehicle_info'),
                    'passenger_count' => data_get($preregisterData, 'passenger_count'),
                    'company_name' => data_get($preregisterData, 'company_name'),
                    'remark' => data_get($preregisterData, 'remark'),
                ]);
            }

            $request->merge([
                'residence_id' => $request->residence_id ?? data_get($preregisterData, 'unit.residence.id'),
                'visited_resident' => $request->visited_resident ?? [
                    'visited_resident' => [
                        'unit_id' => data_get($preregisterData, 'unit_id'),
                        'user_id' => ['user_id' => data_get($preregisterData, 'user_id')],
                    ],
                ],
                'visitor_purpose' => $request->visitor_purpose ?? data_get($preregisterData, 'visitor_purpose'),
                'vehicle_plate_no' => $request->vehicle_plate_no ?? data_get($preregisterData, 'vehicle_plate_no'),
                'arrival_type' => $request->arrival_type ?? data_get($preregisterData, 'arrival_type'),
                'vehicle_type' => $request->vehicle_type ?? data_get($preregisterData, 'vehicle_type'),
                'name' => $request->name ?? data_get($preregisterData, 'visitor.name'),
                'contact_no' => $request->contact_no ?? data_get($preregisterData, 'visitor.contact_no'),
                'id_type' => $request->id_type ?? data_get($preregisterData, 'visitor.id_type'),
                'id_number' => $request->id_number ?? data_get($preregisterData, 'visitor.id_number'),
            ]);
        }

        $residenceId = $request->residence_id;
        $hasIdentityData = ! empty($request->vehicle_plate_no) || ! empty($request->id_number) || ! empty($request->name);

        if (! is_numeric($request->is_allowed) && $hasIdentityData) {
            $blacklistKey = 'blacklist:'.$request->residence_id.':'.md5(
                ($request->vehicle_plate_no ?? '').'|'.($request->id_number ?? '').'|'.($request->name ?? '')
            );

            $blacklistedVisitor = $cache->remember($blacklistKey, $cacheTtlSeconds, function () use ($request) {
                return BlacklistedVisitor::query()
                    ->select(['id', 'visitor_id', 'residence_id', 'vehicle_plate_no', 'blacklist_remark'])
                    ->with('visitor:id,name,id_number')
                    ->where('residence_id', $request->residence_id)
                    ->where(function ($query) use ($request) {
                        if ($request->vehicle_plate_no) {
                            $query->where('vehicle_plate_no', $request->vehicle_plate_no);
                        }
                        $query->orWhereHas('visitor', function ($q) use ($request) {
                            $q->where(function ($subQ) use ($request) {
                                $subQ->where('id_number', $request->id_number)
                                    ->orWhere('name', $request->name);
                            });
                        });
                    })
                    ->first();
            });

            if ($blacklistedVisitor) {
                return [
                    'data' => $blacklistedVisitor,
                    'is_blacklist' => true,
                    'message' => 'Visitor has a blacklist record!',
                ];
            }
        }

        $request->merge([
            'blacklist_remark' => $request->is_allowed == 1 ? $request->blacklist_remark : ($request->is_allowed === 0 ? 'No Entry!' : null),
        ]);

        DB::beginTransaction();

        try {
            if ($preregisterData && ! data_get($preregisterData, 'is_multiple_entry')) {
                PreregisterVisitor::whereKey(data_get($preregisterData, 'id'))
                    ->update(['is_qr_code_expired' => true]);
                if ($preregisterCacheKey) {
                    $cache->forget($preregisterCacheKey);
                }
            }

            $visitor = Visitor::create([
                'name' => $request->name,
                'contact_no' => $request->contact_no,
                'id_type' => $request->id_type,
                'id_number' => $request->id_number,
            ]);

            $now = now();

            $visitorLog = VisitorLog::create([
                'visitor_id' => $visitor->id,
                'residence_id' => $residenceId ?? 0,
                'visitor_generated_no' => VisitorHelper::generateVisitorNo($residenceId),
                'arrival_time' => $now,
                'visitor_code' => $request->visitor_code ?? Str::random(20),
                'visitor_card_id' => $request->visitor_card_id ?? null,
                'visitor_purpose' => $request->visitor_purpose,
                'courier_logistic_partner_id' => $request->courier_logistic_partner_id,
                'food_delivery_logistic_partner_id' => $request->food_delivery_logistic_partner_id,
                'company_name' => $request->company_name,
                'arrival_type' => $request->arrival_type,
                'vehicle_type' => $request->vehicle_type,
                'vehicle_plate_no' => $request->vehicle_plate_no,
                'temperature' => $request->temperature,
                'passenger_count' => $request->passenger_count,
                'remark' => $request->remark,
                'is_allowed' => $request->is_allowed,
                'is_pre_register' => $request->is_pre_register ?? 0,
                'blacklist_remark' => $request->blacklist_remark,
                'vehicle_info' => is_string($request->vehicle_info) ? json_decode($request->vehicle_info, true) : $request->vehicle_info,
                'leave_time' => null,
                'pdpa_status' => $request->hasFile('esign_image') ? 'accepted' : 'none',
            ]);

            $visitingArrangements = [];
            $notificationUserIds = [];
            $visitedResidents = is_array($request->visited_resident) ? $request->visited_resident : [];

            foreach ($visitedResidents as $visited_resident) {
                $rawUserIds = $visited_resident['user_id'] ?? null;
                $userIds = is_array($rawUserIds) ? $rawUserIds : ($rawUserIds !== null ? [$rawUserIds] : []);
                $userIds = array_values(array_filter($userIds, fn ($id) => ! is_array($id) && $id !== ''));

                if (empty($userIds)) {
                    $userIds = [null];
                }

                foreach ($userIds as $userId) {
                    $visitingArrangements[] = [
                        'visitor_log_id' => $visitorLog->id,
                        'residence_id' => $residenceId,
                        'unit_id' => $visited_resident['unit_id'],
                        'user_id' => $userId,
                        'status' => $request->status ?? null,
                        'estamp_by' => $request->estamp_by ?? null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    if ($userId) {
                        $notificationUserIds[] = $userId;
                    }
                }
            }

            if (empty($visitingArrangements)) {
                throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'At least one visiting arrangement is required');
            }

            VisitingArrangement::insert($visitingArrangements);

            DB::commit();

            // Bust stale caches after successful commit
            if (! empty($preregisterData) && ! data_get($preregisterData, 'is_multiple_entry')) {
                $cache->forget("visitor_code:active:{$request->visitor_code}");
            }
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Visitor creation failed', [
                'visitor_code' => $request->visitor_code,
                'residence_id' => $request->residence_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($e instanceof GeneralException) {
                throw $e;
            }

            throw new GeneralException(
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
                'Failed to create visitor log: '.$e->getMessage()
            );
        }

        $mediaFiles = [
            'id_image' => 'id_image',
            'visitor_image' => 'visitor_image',
            'vehicle_image' => 'vehicle_image',
            'esign_image' => 'pdpa_esign',
        ];

        foreach ($mediaFiles as $fileField => $fileType) {
            if ($request->hasFile($fileField)) {
                $file = $request->file($fileField);
                $imageExtension = $file->extension();
                $uniqueFileName = "{$fileType}_".now()->format('YmdHis').'_'.uniqid().".{$imageExtension}";
                $tempDir = "temp/visitor/{$fileField}/{$visitorLog->id}";
                $tempPath = $file->storeAs($tempDir, $uniqueFileName);

                ProcessVisitorImage::dispatch(
                    $visitorLog->id,
                    $tempPath,
                    $fileType,
                    $fileField,
                    $uniqueFileName
                )->onQueue('VisitorQueue');
            }
        }

        $notificationUserIds = array_values(array_unique($notificationUserIds));
        $residenceFeatureEnabled = ResidenceFeature::where('residence_id', $visitorLog->residence_id)
            ->where('feature_id', Features::VISITOR->value)
            ->where('is_active', true)
            ->exists();

        if (! empty($notificationUserIds) && $residenceFeatureEnabled) {
            SendVisitorArrivedNotifications::dispatch($visitorLog->id, $notificationUserIds)
                ->onQueue('VisitorQueue');
        }

        return [
            'data' => $visitorLog,
            'is_blacklist' => false,
            'message' => 'Success',
        ];
    }

    public function show(int $id)
    {
        $visitorAction = new GetOneVisitorAction;
        $visitor = $visitorAction->execute($id);

        VisitorApiResponse::create([
            'visitor_log_id' => $id,
            'arrival_time' => $visitor->arrival_time,
            'leave_time' => $visitor->leave_time,
        ]);

        return $visitor;
    }

    public function update(UpdateVisitorRequest $request, int $id)
    {
        $payload = [
            'leave_time' => now('Asia/Bangkok')->toDateTimeString(),
        ];

        if ($request->filled('visitor_card_id')) {
            $payload['visitor_card_id'] = $request->visitor_card_id;
        }

        $updated = VisitorLog::whereKey($id)->update($payload);

        if (! $updated) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed updating visitor');
        }

        return null;
    }

    public function updateByVisitorCode(UpdateVisitorRequest $request, string $id)
    {
        $visitor_log = VisitorLog::where('visitor_code', $id)->first();
        $visitorAction = new UpdateVisitorLogAction;

        return $visitorAction->execute($request, $visitor_log);
    }

    public function updateByVisitorCard(UpdateVisitorRequest $request, int $visitor_card_id)
    {
        $visitor_log = VisitorLog::where('visitor_card_id', $visitor_card_id)
            ->whereNull('leave_time')
            ->latest('id')
            ->firstOrFail();

        $visitorAction = new UpdateVisitorLogAction;
        $visitorAction->execute($request, $visitor_log);

        return $visitor_log;
    }

    public function scanInScanOut(CreateUpdateVisitorRequest $request, StoreVisitorRequest $storeVisitorRequest, UpdateVisitorRequest $updateVisitorRequest)
    {
        if ($request->has('visitor_card_id')) {
            $visitorLog = VisitorLog::query()
                ->select(['id', 'arrival_type', 'leave_time'])
                ->where('visitor_card_id', $request->visitor_card_id)
                ->whereNull('leave_time')
                ->latest('id')
                ->first();

            if ($visitorLog) {
                $visitorLog->has_visitor_parking = false;

                $this->update($updateVisitorRequest, $visitorLog->id);

                if ($visitorLog->arrival_type == ArrivalType::DRIVE_IN->value) {
                    $visitorLog->has_visitor_parking = true;
                }

                $result = success($visitorLog);
            } else {
                throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Visitor card not attached to any visitor');
            }
        } elseif ($request->has('visitor_code')) {
            $visitorLog = VisitorLog::query()
                ->select(['id', 'arrival_type', 'leave_time', 'visitor_code']) // visitor_code is needed for qrCodeUrl accessor
                ->where('visitor_code', $request->visitor_code)
                ->whereNull('leave_time')
                ->first();

            if ($visitorLog) {
                $visitorLog->has_visitor_parking = false;
                $this->update($updateVisitorRequest, $visitorLog->id);
                
                $visitorLog = VisitorLog::query()
                    ->select(['id', 'residence_id', 'arrival_type', 'leave_time', 'visitor_code', 'vehicle_plate_no'])
                    ->with([
                        'visitingArrangements',
                        'media'
                    ])
                    ->where('id', $visitorLog->id)
                    ->first();

                if ($visitorLog->arrival_type == ArrivalType::DRIVE_IN->value) {
                    $visitorLog->has_visitor_parking = true;
                }
                $result = success($visitorLog);
            } else {
                try {
                    $result = $this->create($storeVisitorRequest);
                } catch (GeneralException $th) {
                    return success(new stdClass, $th->getMessage(), 500);
                }
            }
        }

        return $result;
    }

    public function export(array $request)
    {
        $exportVisitorLogAction = new ExportVisitorLogAction;
        $visitor = $exportVisitorLogAction->execute($request);

        return $visitor;
    }

    public function storeFeedback(array $data)
    {
        // Build base query
        $query = VisitingArrangement::where('visitor_log_id', $data['visitor_log_id']);

        // Add conditional where clause based on available data
        $query->when(
            ! empty($data['user_id']),
            fn ($q) => $q->where('user_id', $data['user_id'])
        );

        $query->when(
            ! empty($data['unit_id']),
            fn ($q) => $q->where('unit_id', $data['unit_id'])
        );

        // Find and update in a single query to avoid race conditions
        $updated = $query->update([
            'feedback_remark' => $data['feedback_remark'],
            'estamp_by' => $data['estamp_by'],
            'estamp_by_type' => $data['estamp_by_type'],
            'status' => VisitingArrangementStatus::CANCEL_BY_SG->value,
        ]);

        if (! $updated) {
            throw new GeneralException(JsonResponse::HTTP_NOT_FOUND, 'Visiting arrangement not found');
        }

        // Return the updated model if needed
        return $query->first();
    }

    public function prebookVisitor(Request $request)
    {
        if ($request->has('visitor_code')) {
            $visitorLog = VisitorLog::where('visitor_code', $request->visitor_code)->whereNull('leave_time')->first();

            if (! $visitorLog) {
                $getPregisterVisitor = new GetOnePreregisterVisitorAction;

                return $getPregisterVisitor->execute($request);
            } else {
                return response()->json(['message' => 'Visitor is exist, proceed to checkout']);
            }
        } else {
            return response()->json(['message' => 'Please Proceed to Scan In/Out']);
        }
    }
}
