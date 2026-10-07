<?php

namespace App\Http\Resources\SupportTicket;

use Illuminate\Http\Request;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use App\Models\Erp\ChatCategory;
use App\Models\SupportTicketComment;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request)
    {
        $submitted_user = User::findOrFail($this['submitted_by']);
        $assigned_user = User::findOrFail($this['assigned_to']);
        $unit = Unit::find($this['unit_id']);

        $unread_indicator = isset($request->user_id) && ((int) $request->user_id == $this['assigned_to']) && is_null($this['read_at']) ? 1 : 0;
        $unread_indicator = isset($request->pm_user_id) && ((int) $request->pm_user_id == $this['assigned_to']) && is_null($this['read_at']) ? 1 : 0;

        if (! empty($this['comments']) && isset($request->user_id)) {
            $comment_indicator = SupportTicketComment::where('commentable_type', 'App\Models\SupportTicket')
                ->where('commentable_id', $this['id'])
                ->where('user_id', '!=', $request->user_id)
                ->whereNull('read_at')
                ->count();

            $unread_indicator = $unread_indicator + $comment_indicator;
        }

        if (! empty($this['comments']) && isset($request->pm_user_id)) {
            $comment_indicator = SupportTicketComment::where('commentable_type', 'App\Models\SupportTicket')
                ->where('commentable_id', $this['id'])
                ->where('user_id', '!=', $request->pm_user_id)
                ->whereNull('read_at')
                ->count();

            $unread_indicator = $unread_indicator + $comment_indicator;
        }

        $unread_indicator = $unread_indicator > 0 ? true : false;

        $chatCategory = is_null($this['chat_category_item_id'])
            ? ChatCategory::find(5)
            : ChatCategory::find($this['chat_category_item']['chat_category']['id'] ?? null);

        $language = $request->header('Accept-Language', 'en-US');
        $categoryName = ($language === 'th' && $chatCategory)
            ? $chatCategory->name_in_thai
            : ($chatCategory ? $chatCategory->name : null);

        return [
            'id' => (int) $this['id'],
            'case_generated_no' => $this['case_generated_no'],
            'unit_id' => (int) $this['unit_id'],
            'unit_number' => isset($unit) ? $unit->unit_number : '',
            'content' => $this['content'],
            'chat_category_id' => $chatCategory ? $chatCategory->id : null,
            'chat_category' => $categoryName,
            'chat_category_item_id' => $this['chat_category_item_id'] ?? null,
            'category' => isset($this['category'])
                ? $this['category']
                : (
                    $this['chat_category_item']['title']
                ),
            'status' => $this['status'],
            'submitted_by' => (int) $this['submitted_by'],
            'submitted_name' => isset($submitted_user) ? $submitted_user->name : '',
            'assigned_to' => (int) $this['assigned_to'],
            'assigned_name' => isset($assigned_user) ? $assigned_user->name : '',
            'read_at' => $this['read_at'],
            'unread_indicator' => $unread_indicator,
            'attachment_url' => $this['attachment_url'],
            'created_at' => $this['created_at'],
        ];
    }
}
