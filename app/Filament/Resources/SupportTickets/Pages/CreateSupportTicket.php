<?php

namespace App\Filament\Resources\SupportTickets\Pages;

use App\Actions\Audit\CreateAuditAction;
use App\Filament\Resources\SupportTickets\SupportTicketResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class CreateSupportTicket extends CreateRecord
{
    protected static string $resource = SupportTicketResource::class;

    public function getTitle(): string
    {
        return __('menu.new_support_ticket');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $data['status'] ??= 'new';
        $record = static::getModel()::create($data);

        $audit = new CreateAuditAction;
        $audit->execute(new Request([
            'user_type' => get_class(auth()->user()),
            'user_id' => auth()->id(),
            'event' => 'created',
            'auditable_type' => get_class($record),
            'auditable_id' => $record->id,
            'old_values' => [],
            'new_values' => $record->makeHidden('media')->toArray(),
            'url' => request()->fullUrl(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]));

        return $record;
    }
}
