<?php

namespace App\Actions\SupportTicket;

use Illuminate\Support\Str;
use App\Http\Integrations\MmbErp\SupportTicket\Requests\CreateSupportTicketRequest;
use Illuminate\Http\Request;

class CreateSupportTicketAction
{
    public function execute(Request $request)
    {
        $request = new CreateSupportTicketRequest($this->supportTicketData($request));
        $response = $request->send();

        return $response->json();
    }

    public function supportTicketData($request)
    {
        $data = [
            ['name' => 'unit_id', 'contents' => $request->unit_id],
            ['name' => 'content', 'contents' => $request->content],
            ['name' => 'platform_identifier', 'contents' => 'mmb'],
            ['name' => 'submitted_by', 'contents' => $request->submitted_by],
            ['name' => 'assigned_to', 'contents' => $request->assigned_to],
        ];

        foreach (['chat_category_id', 'chat_category_item_id', 'category'] as $optionalField) {
            if (! empty($request->$optionalField)) {
                $data[] = ['name' => $optionalField, 'contents' => $request->$optionalField];
            }
        }

        if ($request->hasFile('file')) {
            $files = is_array($request->file('file')) ? $request->file('file') : [$request->file('file')];

            foreach ($files as $file) {
                $randomName = Str::uuid()->toString();
    
                $data[] = [
                    'name' => 'file[]',
                    'contents' => file_get_contents($file->getRealPath()),
                    'filename' => "{$randomName}.{$file->getClientOriginalExtension()}",
                ];
            }
        }

        return $data;
    }
}
