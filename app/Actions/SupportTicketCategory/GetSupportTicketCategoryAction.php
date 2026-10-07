<?php

namespace App\Actions\SupportTicketCategory;

use App\Models\Erp\ChatCategory;
use Illuminate\Http\Request;

class GetSupportTicketCategoryAction
{
    public function execute(Request $request)
    {
        $locale = $request->header('Accept-Language', 'en-US');

        $nameColumn = $locale === 'th' ? 'name_in_thai' : 'name';

        $others = $locale === 'th' ? 'อื่นๆ' : 'Others';

        $query = ChatCategory::selectRaw("id, $nameColumn as name")->where($nameColumn, '!=', $others);

        if ($request->filled('name')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', '%'.$request->name.'%')
                    ->orWhere('name_in_thai', 'LIKE', '%'.$request->name.'%');
            });
        }

        return $query->get();
    }
}
