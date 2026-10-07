<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Exports\PatrolCheckPoints\ExportPatrolCheckPointLogs;
use App\Services\SgocGraphQLService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class PatrolCheckPointLogController extends Controller
{
    /**
     * Patrol Check Point Log Data
     *
     * @param  Request  $request
     * @return BinaryFileResponse
     */
    public function export(Request $request)
    {
        $inputs = $request->all();

        $data = $this->getDataExport($inputs);

        return Excel::download(new ExportPatrolCheckPointLogs($data), 'MyMooBan Bill Patrol Check Point Logs.xlsx');
    }

    private function getDataExport($request)
    {
        $query = <<<'GQL'
                    query(
                        $page: Int!,
                        $checkpointName: QueryPaginatedCheckpointLogsHasCheckpointWhereHasConditions,
                        $sgStaffName: QueryPaginatedCheckpointLogsHasUserWhereHasConditions
                        ) {
                        paginatedCheckpointLogs(
                            page: $page,
                            hasCheckpoint: $checkpointName,
                            hasUser: $sgStaffName
                            orderBy: [
                                {
                                column: "created_at"
                                order: DESC
                                }
                            ]
                            ) {
                            data {
                                id
                                checkpoint_id
                                user_id
                                questionnaires
                                remark
                                image_url
                                checkpoint {
                                    mmb_residence_id
                                    name
                                    description
                                    is_active
                                }
                                user {
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
                    }
                    GQL;

        $variables['page'] = is_null($request['page']) == false ? (int) $request['page'] : 1;

        if (is_null($request['checkpoint_name']) == false) {
            $variables['checkpointName'] = [
                'column' => 'NAME',
                'operator' => 'LIKE',
                'value' => '%'.$request['checkpoint_name'].'%',
            ];
        } else {
            $variables['checkpointName'] = null;
        }

        if (is_null($request['sg_staff_name']) == false) {
            $variables['sgStaffName'] = [
                'column' => 'NAME',
                'operator' => 'LIKE',
                'value' => '%'.$request['sg_staff_name'].'%',
            ];
        } else {
            $variables['sgStaffName'] = null;
        }

        $response = SgocGraphQLService::execute($query, $variables);
        $patrolCheckpointLogs = collect(data_get($response->json(), 'data.paginatedCheckpointLogs'));

        return $patrolCheckpointLogs;
    }
}
