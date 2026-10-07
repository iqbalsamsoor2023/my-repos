<?php

namespace App\Filament\Resources\FaqTypes\Pages;

use App\Filament\Resources\FaqTypes\FaqTypeResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateFaqType extends CreateRecord
{
    protected static string $resource = FaqTypeResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $faqs = $data['faqs'] ?? [];
        unset($data['faqs']);

        $record = static::getModel()::create($data);

        foreach ($faqs as $faqItem) {
            $record->faqs()->create([
                'question' => [
                    'en' => $faqItem['question'] ?? '',
                    'th' => $faqItem['question_th'] ?? '',
                ],
                'answer' => [
                    'en' => $faqItem['answer'] ?? '',
                    'th' => $faqItem['answer_th'] ?? '',
                ],
            ]);
        }

        return $record;
    }
}
