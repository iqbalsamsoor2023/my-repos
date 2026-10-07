<?php

namespace App\Services;

use App\Actions\SupportTicketCategory\GetSupportTicketCategoryAction;
use App\Actions\SupportTicketCategory\GetSupportTicketCategoryTopicAction;
use Illuminate\Http\Request;

class SupportTicketCategoryService
{
    public function index(Request $request)
    {
        $getSupportTicketCategoryAction = new GetSupportTicketCategoryAction;
        $response = $getSupportTicketCategoryAction->execute($request);

        return $response;
    }

    public function indexCategoryTopic(Request $request)
    {
        $getSupportTicketCategoryTopicAction = new GetSupportTicketCategoryTopicAction;
        $response = $getSupportTicketCategoryTopicAction->execute($request);

        return $response;
    }
}
