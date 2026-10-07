<?php

namespace App\Filament\Resources\SupportTickets\Pages;

use App\Filament\Resources\SupportTickets\SupportTicketResource;
use App\Models\Audit;
use App\Models\SupportTicket;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSupportTicket extends EditRecord
{
    protected static string $resource = SupportTicketResource::class;

    public function getTitle(): string
    {
        return __('menu.edit_support_ticket');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $support_ticket = SupportTicket::whereId($data['id'])->first();
        $data['residence_id'] = $support_ticket?->unit?->residence_id;
        $data['chat_category_id'] = $support_ticket?->chatCategoryItem?->chatCategory?->id ?? 5;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $original = $record->getOriginal();

        // Fill the record with new data (but not saved yet)
        $record->fill($data);

        $changes = collect($record->getDirty())->except('media')->all(); // Get only changed attributes

        if (! empty($changes)) {
            $record->save();

            Audit::create([
                'user_type' => get_class(auth()->user()),
                'user_id' => auth()->id(),
                'event' => 'updated',
                'auditable_type' => get_class($record),
                'auditable_id' => $record->id,
                'old_values' => array_intersect_key($original, $changes),
                'new_values' => $changes,
                'url' => request()->fullUrl(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }

        return $record;
    }
}
