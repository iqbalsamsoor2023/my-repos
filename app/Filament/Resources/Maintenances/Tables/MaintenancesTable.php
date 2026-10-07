<?php

namespace App\Filament\Resources\Maintenances\Tables;

use App\Actions\Audit\CreateAuditAction;
use App\Enums\Maintenance\MaintenanceStatus;
use App\Enums\Maintenance\MaintenanceVerificationStatus;
use App\Exports\MaintenanceExport;
use App\Filament\Resources\PrivateMaintenances\Pages\ListPrivateMaintenances;
use App\Filament\Resources\PublicMaintenances\Pages\ListPublicMaintenances;
use App\Filament\Resources\Units\RelationManagers\MaintenancesRelationManager as PrivateMaintenancesRelationManager;
use App\Models\Amenity;
use App\Models\Maintenance;
use App\Models\Residence;
use App\Models\ResidenceAmenity;
use App\Models\ResidenceAmenityOption;
use App\Models\Unit;
use App\Services\FilamentExport\FilamentExportBulkAction;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class MaintenancesTable
{
    public static function configure(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->columns([
                TextColumn::make('maintainable_claim_number')
                    ->label(__('maintenance.work_order_number'))
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('maintainable_type')
                    ->label(__('residence.residence'))
                    ->formatStateUsing(function ($record) {
                        $maintainable = $record->maintainable;

                        return match (true) {
                            $maintainable instanceof ResidenceAmenity => optional($maintainable?->residence)->name,
                            $maintainable instanceof ResidenceAmenityOption => optional($maintainable?->residenceAmenity?->residence)->name,
                            $maintainable instanceof Unit => optional($maintainable?->moobaan)->name,
                            default => null,
                        };
                    })
                    ->hidden(fn (Component $livewire): bool => $livewire instanceof PrivateMaintenancesRelationManager)
                    ->toggleable()
                    ->description(function ($record) {
                        $maintainable = $record->maintainable;

                        return match (true) {
                            $maintainable instanceof ResidenceAmenity => optional($maintainable?->residence)->name_th,
                            $maintainable instanceof ResidenceAmenityOption => optional($maintainable?->residenceAmenity?->residence)->name_th,
                            $maintainable instanceof Unit => optional($maintainable?->moobaan)->name_th,
                            default => null,
                        };
                    }),
                TextColumn::make('maintainable.unit_number')
                    ->label(__('unit.unit_number'))
                    ->hidden(fn (Component $livewire): bool => $livewire instanceof ListPublicMaintenances || $livewire instanceof PrivateMaintenancesRelationManager)
                    ->toggleable(),
                TextColumn::make('maintainable_id')
                    ->label(__('Amenity/Facility'))
                    ->formatStateUsing(function ($record) {
                        $maintainable = $record->maintainable;

                        return match (true) {
                            $maintainable instanceof ResidenceAmenity => optional($maintainable?->facilityAndAmenity)->name,
                            $maintainable instanceof ResidenceAmenityOption => $maintainable->residenceAmenity && $maintainable->residenceAmenity->facilityAndAmenity
                                ? $maintainable->residenceAmenity->facilityAndAmenity->name . ' (' . $maintainable->name . ')'
                                : '-',

                            default => null,
                        };
                    })
                    ->description(function ($record) {
                        $maintainable = $record->maintainable;

                        return match (true) {
                            $maintainable instanceof ResidenceAmenity => optional($maintainable?->facilityAndAmenity)->name_in_thai,
                            $maintainable instanceof ResidenceAmenityOption => $maintainable->residenceAmenity && $maintainable->residenceAmenity->facilityAndAmenity
                                ? $maintainable->residenceAmenity->facilityAndAmenity->name_in_thai . ' (' . $maintainable->name_in_thai . ')'
                                : '-',

                            default => null,
                        };
                    })
                    ->hidden(fn (Component $livewire): bool => $livewire instanceof ListPrivateMaintenances || $livewire instanceof PrivateMaintenancesRelationManager)
                    ->toggleable(),
                TextColumn::make('amenity')
                    ->label(__('maintenance.amenity'))
                    ->hidden(fn (Component $livewire): bool => $livewire instanceof ListPublicMaintenances)
                    ->getStateUsing(function (Maintenance $record) {
                        if (is_null($record->claimable_item_details) == false) {
                            return $record->claimable_item_details['amenity_name'];
                        }

                        return 'Others';
                    })
                    ->toggleable(),
                TextColumn::make('privateClaimCategory.display_name')
                    ->label(__('maintenance.private_claim_category'))
                    ->sortable()
                    ->getStateUsing(
                        fn ($record) =>
                        $record->privateClaimCategory?->display_name ?: 'Others'
                    )
                    ->visible(fn (Component $livewire): bool => $livewire instanceof ListPrivateMaintenances),
                TextColumn::make('privateClaimItem.display_name')
                    ->label(__('maintenance.private_claim_item'))
                    ->sortable()
                    ->getStateUsing(
                        fn ($record) =>
                        $record->privateClaimItem?->display_name ?: 'Others'
                    )
                    ->visible(fn (Component $livewire): bool => $livewire instanceof ListPrivateMaintenances),
                    TextColumn::make('private_claim_item_title')
                    ->label(__('maintenance.private_claim_item_title'))
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        $title = $record->privateClaimItemTitle;
                
                        if ($title && filled($title->display_name)) {
                            return $title->display_name;
                        }
                
                        return filled($record->other_private_claim_item)
                            ? $record->other_private_claim_item
                            : 'Others';
                    })
                    ->visible(fn (Component $livewire): bool => $livewire instanceof ListPrivateMaintenances),
                TextColumn::make('issue_description')
                    ->label(__('maintenance.issue_description'))
                    ->toggleable(),
                TextColumn::make('warranty_status')
                    ->label(__('maintenance.warranty_status'))
                    ->badge()
                    ->colors([
                        'danger' => fn ($state): bool => $state === 'expired',
                        'success' => fn ($state): bool => $state === 'warranty',
                    ])
                    ->getStateUsing(function (Maintenance $record) {

                        $details = $record->claimable_item_details;

                        if (!$details || !isset($details['id'])) {
                            return null;
                        }

                        $amenity = Amenity::find($details['id']);

                        if (!$amenity || !$record->maintainable?->move_in_at) {
                            return null;
                        }

                        $moveIn = Carbon::parse($record->maintainable->move_in_at);

                        $warrantyEnd = $amenity->period_type === 'year'
                            ? $moveIn->copy()->addYears($amenity->warranty_period)
                            : $moveIn->copy()->addMonths($amenity->warranty_period);

                        return now()->gt($warrantyEnd)
                            ? 'expired'
                            : 'in warranty';
                    })
                    ->toggleable()
                    ->visible(fn (Component $livewire): bool => $livewire instanceof ListPrivateMaintenances),
                TextColumn::make('status')
                    ->label(__('app.status'))
                    ->badge()
                    ->formatStateUsing(function (string $state): string {
                        $status = MaintenanceStatus::tryFrom((int) $state);
                        return $status ? __($status->label()) : '-';
                    })
                    ->color(function (string $state): string {
                        return match ((int) $state) {
                            MaintenanceStatus::PENDING->value => 'danger',
                            MaintenanceStatus::IN_PROGRESS->value => 'warning',
                            MaintenanceStatus::COMPLETE->value => 'success',
                            default => 'primary',
                        };
                    })
                    ->toggleable(),
                TextColumn::make('is_verified')
                    ->label(__('app.is_verified'))
                    ->badge()
                    ->colors([
                        'success' => fn ($state) => $state === MaintenanceVerificationStatus::VERIFIED->label(),
                        'danger'  => fn ($state) => $state === MaintenanceVerificationStatus::NOT_VERIFIED->label(),
                    ])
                    ->visible(function () use ($user) {
                        if ($user->hasRole(['Property Management'])) {
                            $residence = Residence::with('warrantySetting')->where('property_management_user_id', $user->id)->first();

                            return $residence?->warrantySetting?->has_verification;
                        }

                        return true;
                    })
                    ->toggleable(),
                TextColumn::make('appointment_datetime')
                    ->label(__('maintenance.appointment_datetime'))
                    ->getStateUsing(function (Maintenance $record) {
                        if (is_null($record->appointment_datetime) == false) {
                            return date('d-M-y H:i:s', strtotime($record->appointment_datetime));
                        }
                    })
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label(__('app.last_update'))
                    ->getStateUsing(function (Maintenance $record) {
                        return date('d-M-y H:i:s', strtotime($record->updated_at));
                    })
                    ->toggleable(),
                TextColumn::make('rating')
                    ->label(__('maintenance.rating'))
                    ->translateLabel()
                    ->getStateUsing(function (Maintenance $record) {
                        if (is_null($record->rating) == false) {
                            if ($record->rating == 0) {
                                $rating = '<p>&#10032;&#10032;&#10032;&#10032;&#10032;</p>';
                            } elseif ($record->rating == 1) {
                                $rating = '<p>&#11088;&#10032;&#10032;&#10032;&#10032;</p>';
                            } elseif ($record->rating == 2) {
                                $rating = '<p>&#11088;&#11088;&#10032;&#10032;&#10032;</p>';
                            } elseif ($record->rating == 3) {
                                $rating = '<p>&#11088;&#11088;&#11088;&#10032;&#10032;</p>';
                            } elseif ($record->rating == 4) {
                                $rating = '<p>&#11088;&#11088;&#11088;&#11088;&#10032;</p>';
                            } elseif ($record->rating == 5) {
                                $rating = '<p>&#11088;&#11088;&#11088;&#11088;&#11088;</p>';
                            } else {
                                $rating = '<p>&#10032;&#10032;&#10032;&#10032;&#10032;</p>';
                            }
                        } else {
                            $rating = '<p>&#10032;&#10032;&#10032;&#10032;&#10032;</p>';
                        }

                        return new HtmlString($rating);
                    })
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('app.created_at_date'))
                    ->getStateUsing(function (Maintenance $record) {
                        return $record->created_at->format('d-M-y');
                    })
                    ->toggleable(),
                TextColumn::make('created_at_time')
                    ->label(__('app.created_at_time'))
                    ->getStateUsing(function (Maintenance $record) {
                        return $record->created_at->format('H:i:s');
                    })
                    ->toggleable(),
                TextColumn::make('reportedBy.name')
                    ->label(__('maintenance.reported_by'))
                    ->toggleable(),
                TextColumn::make('reportedBy.phone_no')
                    ->label(__('maintenance.reporter_phone_number'))
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    ViewAction::make(),
                    DeleteAction::make(),
                    Action::make('export-private')
                        ->label(__('app.export'))
                        ->icon('heroicon-o-document-chart-bar')
                        ->visible(fn (Component $livewire): bool => $livewire instanceof ListPrivateMaintenances)
                        ->url(fn (Maintenance $record): string => route('maintenance.private-export-report-detail', $record->id)),
                    Action::make('export')
                        ->label(__('app.export'))
                        ->icon('heroicon-o-document-chart-bar')
                        ->visible(fn (Component $livewire): bool => $livewire instanceof ListPublicMaintenances)
                        ->url(fn (Maintenance $record): string => route('maintenance.public-export-report-detail', $record->id)),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                FilamentExportBulkAction::make('export')
                    ->fileName('Maintenance-Report')
                    ->disableAdditionalColumns()
                    ->disableCsv()
                    ->disablePdf()
                    ->disableFilterColumns()
                    ->action(function (array $data, Component $livewire) use ($user) {
                        // Visible table columns
                        $columns = collect($livewire->getTable()->getColumns())
                            ->filter(fn ($column) => $column->isVisible())
                            ->map(fn ($column) => $column->getName())
                            ->values()
                            ->all();

                        // bulk action form data
                        $fileName = ($data['file_name'] ?? 'Maintenance-Report') . '.xlsx';

                        // Selected records
                        $maintenances = $livewire->getSelectedTableRecords();

                        $maintenances = Maintenance::whereIn('id', $maintenances->pluck('id'))
                            ->latest('id')
                            ->get();

                        $isClaimableItemExport = $maintenances->contains(function ($maintenance) {
                            return $maintenance->maintainable_type === ResidenceAmenity::class
                                || $maintenance->maintainable_type === ResidenceAmenityOption::class;
                        });

                        $isUnitExport = $maintenances->contains(function ($maintenance) {
                            return $maintenance->maintainable_type === Unit::class;
                        });

                        if ($isClaimableItemExport) {
                            $exportType = 'exported public maintenance records';
                        } elseif ($isUnitExport) {
                            $exportType = 'exported private maintenance records';
                        } else {
                            $exportType = 'Unknown';
                        }

                        $audit = new CreateAuditAction();
                        $audit->execute(new Request([
                            'user_type' => get_class($user),
                            'user_id' => $user->id,
                            'event' => 'exported',
                            'old_values' => [],
                            'new_values' => ['action' => $exportType],
                            'url' => request()->fullUrl(),
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                        ]));

                        return Excel::download(new MaintenanceExport($maintenances, $columns), $fileName);
                    }),
                DeleteBulkAction::make()
                    ->hidden($user->hasAnyRole(['Admin'])),
            ]);
    }
}
