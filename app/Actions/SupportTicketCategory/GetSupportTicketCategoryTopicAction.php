<?php

namespace App\Actions\SupportTicketCategory;

use App\Models\Erp\ChatCategoryItem;
use Illuminate\Http\Request;

class GetSupportTicketCategoryTopicAction
{
    public function execute(Request $request)
    {
        $locale = $request->header('Accept-Language', 'en-US');

        $nameColumn = $locale === 'th' ? 'title_in_thai' : 'title';

        $query = ChatCategoryItem::selectRaw("id, $nameColumn as title")
            ->where('platform_identifier', 'mmb')
            ->where('is_active', true);

        if (isset($request->chat_category_id)) {
            $query->where('chat_category_id', $request->chat_category_id);
        }

        if (isset($request->title)) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'LIKE', '%'.$request->title.'%')
                    ->orWhere('title_in_thai', 'LIKE', '%'.$request->title.'%');
            });
        }

        return $query->get();
    }
}
