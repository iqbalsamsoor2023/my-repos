<?php

namespace App\Actions\SupportTicket;

use App\Http\Integrations\MmbErp\SupportTicket\Requests\CreateSupportTicketCommentRequest;
use Illuminate\Http\Request;

class CreateCommentSupportTicketAction
{
    public function execute(Request $request)
    {
        $request = new CreateSupportTicketCommentRequest($this->supportTicketCommentData($request));
        $response = $request->send();

        return $response->json();
    }

    public function supportTicketCommentData($request)
    {
        $data = [
            [
                'name' => 'user_id',
                'contents' => $request->user_id,
            ],
            [
                'name' => 'commentable_type',
                'contents' => 'App\Models\SupportTicket',
            ],
            [
                'name' => 'commentable_id',
                'contents' => (int) $request->commentable_id,
            ],
        ];

        if (empty($request->content) == false) {
            $data[] = [
                'name' => 'content',
                'contents' => $request->content,
            ];
        }

        if ($request->hasFile('comment_image')) {
            $random_string = strtolower(md5(uniqid(rand(), true)));
            $file_name =
                substr($random_string, 0, 8).'-'.
                substr($random_string, 8, 4).'-'.
                substr($random_string, 12, 4).'-'.
                substr($random_string, 16, 4).'-'.
                substr($random_string, 20);

            $data[] = [
                'name' => 'comment_image',
                'contents' => file_get_contents($request->comment_image),
                'filename' => $file_name.'.'.$request->comment_image->getClientOriginalExtension(),
            ];
        }

        return $data;
    }
}
