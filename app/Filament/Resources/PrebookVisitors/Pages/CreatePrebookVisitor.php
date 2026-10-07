<?php

namespace App\Filament\Resources\PrebookVisitors\Pages;

use App\Filament\Resources\PrebookVisitors\PrebookVisitorResource;
use App\Models\Visitor;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreatePrebookVisitor extends CreateRecord
{
    protected static string $resource = PrebookVisitorResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['visitor_code'] = Str::random(20);

        if ($data['visitor_purpose'] == 'Other') {
            $data['visitor_purpose'] = $data['visitor_purpose_other'];
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $visitor = Visitor::where('id_number', $this->data['visitor']['id_number'])->where('name', $this->data['visitor']['name'])->first();

        if (! $visitor) {
            $visitor = Visitor::create([
                'name' => $this->data['visitor']['name'],
                'id_type' => $this->data['visitor']['id_type'],
                'id_number' => $this->data['visitor']['id_number'],
            ]);
        }

        return static::getModel()::create(array_merge($data, [
            'visitor_id' => $visitor->id,
        ]));
    }
}
