<?php

namespace App\Filament\Resources\BlacklistVisitors\Pages;

use App\Filament\Resources\BlacklistVisitors\BlacklistVisitorResource;
use App\Models\Visitor;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBlacklistVisitor extends CreateRecord
{
    protected static string $resource = BlacklistVisitorResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $visitor = Visitor::where('id_number', $data['id_number'])->first();

        if (! $visitor) {
            $visitor = Visitor::create([
                'name' => $data['name'],
                'id_type' => $data['id_type'],
                'id_number' => $data['id_number'],
            ]);
        }

        return static::getModel()::create(array_merge($data, [
            'visitor_id' => $visitor->id,
        ]));
    }
}
