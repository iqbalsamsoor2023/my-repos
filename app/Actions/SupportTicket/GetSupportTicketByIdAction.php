<?php

namespace App\Actions\SupportTicket;

use App\Exceptions\GeneralException;
use App\Models\SupportTicket;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetSupportTicketByIdAction
{
    public function execute(int $id, Request $request)
    {
        $response = $this->ticketListByIdQuery($id);

        if ($response) {
            $unit = Unit::select('unit_number')->find($response->unit_id);
            $assigned_to = User::select('name')->find($response->assigned_to);
            $submitted_by = User::select('name')->find($response->submitted_by);

            $language = $request->header('Accept-Language', 'en');

            $categoryTitle = $response->category
                ?? ($language === 'th'
                    ? $response->chatCategoryItem->title_in_thai ?? null
                    : $response->chatCategoryItem->title ?? null);

            $mainCategory = $response->category
                ?? ($language === 'th'
                    ? $response->chatCategoryItem?->chatCategory->name_in_thai ?? null
                    : $response->chatCategoryItem?->chatCategory->name ?? null);

            // Check if support ticket or any comment has read_at == null
            $hasUnreadItems = is_null($response->read_at) || $response->comments->contains(function ($comment) {
                return is_null($comment->read_at);
            });

            return [
                'id' => $response->id,
                'unit_id' => (int) $response->unit_id,
                'unit' => $unit->unit_number,
                'case_generated_no' => $response->case_generated_no,
                'chat_category_item_id' => $response->chat_category_item_id,
                'platform_identifier' => $response->platform_identifier,
                'content' => $response->content,
                'main_category' => $mainCategory,
                'category' => $categoryTitle,
                'submitted_by' => $response->submitted_by,
                'submitted_by_name' => $submitted_by?->name,
                'assigned_to' => $response->assigned_to,
                'assigned_to_name' => $assigned_to?->name,
                'read_at' => $response->read_at,
                'has_unread_ticket_or_comment' => $hasUnreadItems,
                'status' => $response->status,
                'created_at' => $response->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $response->updated_at->format('Y-m-d H:i:s'),
                'attachment_url' => $response->attachment_url,
                'attachment_urls' => $response->attachment_urls,
                'chat_category_item' => [
                    'id' => $response->chatCategoryItem?->id,
                    'chat_category_id' => $response->chatCategoryItem?->chat_category_id,
                    'title' => $language === 'th'
                        ? $response->chatCategoryItem?->title_in_thai
                        : $response->chatCategoryItem?->title,
                    'chat_category' => [
                        'id' => $response->chatCategoryItem?->chatCategory?->id,
                        'name' => $language === 'th'
                            ? $response->chatCategoryItem?->chatCategory?->name_in_thai
                            : $response->chatCategoryItem?->chatCategory?->name,
                    ],
                ],
                'comments' => $response->comments->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'user_id' => $comment->user_id,
                        'commentable_type' => $comment->commentable_type,
                        'commentable_id' => $comment->commentable_id,
                        'content' => $comment->content,
                        'read_at' => $comment->read_at,
                        'created_at' => optional($comment->created_at)->format('Y-m-d H:i:s'),
                        'updated_at' => optional($comment->updated_at)->format('Y-m-d H:i:s'),
                        'media_url' => $comment->media_url,
                    ];
                })->toArray(),
            ];
        }

        throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed');
    }

    private function ticketListByIdQuery(int $id)
    {
        return SupportTicket::with(['comments', 'chatCategoryItem.chatCategory'])->find($id);
    }
}
