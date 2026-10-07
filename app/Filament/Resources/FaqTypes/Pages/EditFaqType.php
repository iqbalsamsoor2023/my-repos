<?php

namespace App\Filament\Resources\FaqTypes\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\FaqTypes\FaqTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditFaqType extends EditRecord
{
    protected static string $resource = FaqTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record->loadMissing('faqs'); // eager load faqs relationship

        $data['faqs'] = [];

        foreach ($record->faqs as $faq) {
            $data['faqs'][] = [
                'id' => $faq->id,
                'question' => $faq->question['en'] ?? '',
                'question_th' => $faq->question['th'] ?? '',
                'answer' => $faq->answer['en'] ?? '',
                'answer_th' => $faq->answer['th'] ?? '',
            ];
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Extract and remove the faqs from the form data
        $faqs = $data['faqs'] ?? [];
        unset($data['faqs']);

        $record->update($data);

        $incomingFaqIds = [];

        foreach ($faqs as $faqItem) {
            if (isset($faqItem['id']) && $faq = $record->faqs()->find($faqItem['id'])) {
                $faq->update([
                    'question' => [
                        'en' => $faqItem['question'] ?? '',
                        'th' => $faqItem['question_th'] ?? '',
                    ],
                    'answer' => [
                        'en' => $faqItem['answer'] ?? '',
                        'th' => $faqItem['answer_th'] ?? '',
                    ],
                ]);
                $incomingFaqIds[] = $faq->id;
            } else {
                $newFaq = $record->faqs()->create([
                    'question' => [
                        'en' => $faqItem['question'] ?? '',
                        'th' => $faqItem['question_th'] ?? '',
                    ],
                    'answer' => [
                        'en' => $faqItem['answer'] ?? '',
                        'th' => $faqItem['answer_th'] ?? '',
                    ],
                ]);
                $incomingFaqIds[] = $newFaq->id;
            }
        }

        // Delete FAQs that are no longer present in the submitted data
        $existingFaqIds = $record->faqs()->pluck('id')->toArray();

        $idsToDelete = array_diff($existingFaqIds, $incomingFaqIds);

        if (! empty($idsToDelete)) {
            $record->faqs()->whereIn('id', $idsToDelete)->delete();
        }

        return $record;
    }
}
