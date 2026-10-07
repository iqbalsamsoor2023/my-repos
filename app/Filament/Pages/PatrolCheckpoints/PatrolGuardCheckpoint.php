<?php

namespace App\Filament\Pages\PatrolCheckpoints;

use App\Models\Residence;
use App\Services\SgocGraphQLService;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class PatrolGuardCheckpoint extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-plus';

    protected string $view = 'filament.pages.patrol-checkpoints.patrol-guard-checkpoint';

    protected static ?string $slug = 'patrol-guard-checkpoints';

    protected static bool $shouldRegisterNavigation = false;

    // protected static function shouldRegisterNavigation(): bool
    // {
    //     return auth()->user()->hasRole(['Super Admin', 'Property Management', 'Property Management Operation Center']);
    // }

    public static function getNavigationGroup(): string
    {
        return __('menu.security_data');
    }

    protected function getTableQuery()
    {
        $user = auth()->user();

        $page = is_null(request()->query('page')) == false ? (int) request()->query('page') : 1;
        $created_at = '';
        $where_condition = '';
        $mmb_residence_id = '';

        $name = is_null(request()->query('name')) == false ? 'name:'.('"%'.request()->query('name').'%"').'' : '';
        $description = is_null(request()->query('description')) == false ? 'description:'.('"%'.request()->query('description').'%"').'' : '';
        $is_active = is_null(request()->query('is_active')) == false ? 'is_active:'.(int) request()->query('is_active').'' : '';
        $column = '"id"';

        if (is_null(request()->query('dateFrom')) == false && is_null(request()->query('dateTo')) == false) {
            $dateFrom = '"'.Carbon::parse(request()->query('dateFrom'), config('app.timezone'))->startOfDay()->format('Y-m-d').'"';
            $dateTo = '"'.Carbon::parse(request()->query('dateTo'), config('app.timezone'))->endOfDay()->format('Y-m-d').'"';

            $created_at = "created_at: {from: $dateFrom to: $dateTo}";
        }

        if ($user->hasRole('Property Management')) {
            $residence = get_residence_by_property_management($user->id);

            $where_condition = "where: {column:MMB_RESIDENCE_ID, operator:IN, value: [$residence->id]}";
        } elseif ($user->hasRole('Property Management Operation Center')) {
            $residenceIds = get_residence_by_property_management_operation_center($user->id);

            $where_condition = "where: {column:MMB_RESIDENCE_ID, operator:IN, value: $residenceIds}";
        }

        if (is_null(request()->query('residence')) == false) {
            $dataResidence = Residence::where('name', 'LIKE', '%'.request()->query('residence').'%')
                ->orWhere('name_th', 'LIKE', '%'.request()->query('residence').'%')
                ->select('id', 'name', 'name_th')
                ->first();

            if ($dataResidence) {
                $mmb_residence_id = 'mmb_residence_id:'.(int) $dataResidence->id.'';
            }
        }

        $query = "query {
                    paginatedCheckpoints(
                        page: $page
                        $mmb_residence_id
                        $name
                        $description
                        $is_active
                        $where_condition
                        $created_at
                        orderBy: [
                            {
                            column: $column
                            order: DESC
                            }
                        ]
                    ) {
                        data {
                            id
                            mmb_residence_id
                            name
                            description
                            is_active
                            residence {
                                name
                            }
                            created_at
                        }
                        paginatorInfo {
                            count
                            currentPage
                            hasMorePages
                            total
                        }
                    }
                }";

        $response = SgocGraphQLService::execute($query);
        $patrolGuardCheckpoints = collect(data_get($response->json(), 'data.paginatedCheckpoints'));

        return $this->indexDatatable($patrolGuardCheckpoints);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  array  $data
     */
    private function indexDatatable($data): JsonResponse
    {
        // Transformer
        $dataCollection = new Collection([
            'data' => $this->indexMap($data['data']),
            'pagination' => $data['paginatorInfo'],
        ]);

        return response()->json($dataCollection);
    }

    /**
     * Transformer for DT data attributes
     */
    private function indexMap(array $data = []): array
    {
        $modelMapping = [];

        foreach ($data as $data) {
            $modelMapping[] = [
                'id' => data_get($data, 'id', ''),
                'residence' => data_get($data, 'residence.name', '-'),
                'name' => data_get($data, 'name', '-'),
                'description' => data_get($data, 'description', '-'),
                'is_active' => data_get($data, 'is_active', '-'),
                'created_at_date' => Carbon::parse(data_get($data, 'created_at'))->format('Y-m-d'),
                'created_at_time' => Carbon::parse(data_get($data, 'created_at'))->format('H:i'),
            ];
        }

        return $modelMapping;
    }
}
