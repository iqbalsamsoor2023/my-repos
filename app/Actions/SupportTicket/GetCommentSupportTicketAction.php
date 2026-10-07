<?php

namespace App\Actions\SupportTicket;

use App\Exceptions\GeneralException;
use App\Http\Integrations\MmbErp\SupportTicket\Requests\GetSupportTicketCommentRequest;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetCommentSupportTicketAction
{
    public function execute(Request $request)
    {
        $responses = $this->commentSupportTicketQuery($request);

        if ($responses['http_code'] == 200) {
            $datas = [];

            // Defensive: check if data and 'data' key exist and is array
            $comments = $responses['data']['data'] ?? [];

            if (count($comments) > 0) {
                foreach ($comments as $response) {
                    $user = User::find($response['user_id']);
                    if (! $user) {
                        continue;
                    }

                    $datas[] = array_merge($response, [
                        'user_name' => $user->name,
                        'user_profile_url' => $user->profile_image_url,
                        'unread_indicator' => isset($request->user_id) && is_null($response['read_at']),
                    ]);
                }
            }

            return [
                'data' => $datas,
                'paginatorInfo' => [
                    'count' => $responses['data']['per_page'] ?? 0,
                    'currentPage' => $responses['data']['current_page'] ?? 1,
                    'hasMorePages' => $responses['data']['next_page_url'] !== null,
                    'total' => $responses['data']['total'] ?? 0,
                ],
            ];
        }

        throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed');
    }

    private function commentSupportTicketQuery($request)
    {
        $request->merge([
            'commentable_type' => SupportTicket::class,
        ]);

        $request = new GetSupportTicketCommentRequest($request->all());
        $response = $request->send();

        return $response->json();
    }
}
