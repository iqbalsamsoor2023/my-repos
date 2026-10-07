<?php

namespace App\Filament\Resources\CheckPointLogs\Schemas;

use App\Enums\Checkpoint\CheckpointLogStatus;
use App\Enums\CheckpointQuestionnaire\AnswerType;
use App\Forms\Components\Checkpoint\Image;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class CheckPointLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Checkpoint Logs'))
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('checkpoint.residence.name')
                            ->label(__('app.mooban_or_residence'))
                            ->inlineLabel()
                            ->getStateUsing(function ($record) {
                                return app()->getLocale() === 'th'
                                    ? $record?->checkpoint?->residence->name_th
                                    : $record?->checkpoint?->residence->name;
                            })
                            ->color('primary'),
                        TextEntry::make('user.name')
                            ->label(__('checkpoint.checked_by'))
                            ->inlineLabel(),
                        TextEntry::make('checkpoint.name')
                            ->label(__('checkpoint.checkpoint_name'))
                            ->inlineLabel(),
                        TextEntry::make('checkpoint_round_id')
                            ->label(__('checkpoint.start_time_end_time'))
                            ->formatStateUsing(function ($state, $record) {
                                return "{$record->checkpointRound->round->start_time} - {$record->checkpointRound->round->end_time}";
                            })
                            ->inlineLabel(),
                        TextEntry::make('latitude')
                            ->label(__('checkpoint.latitude'))
                            ->inlineLabel(),
                        TextEntry::make('longitude')
                            ->label(__('checkpoint.longitude'))
                            ->inlineLabel(),
                        TextEntry::make('remark')
                            ->label(__('checkpoint.remark'))
                            ->inlineLabel(),
                        TextEntry::make('status')
                            ->label(__('checkpoint.status'))
                            ->inlineLabel(),
                        TextEntry::make('skip_checkpoint_remark')
                            ->label(__('checkpoint.skip_remark'))
                            ->visible(fn ($record) => $record->status?->value == CheckpointLogStatus::SKIP->value) // used ?->value to bypass casted enum issue
                            ->inlineLabel(),
                        SpatieMediaLibraryImageEntry::make('image')
                            ->disk('cos')
                            ->label(__('app.image'))
                            ->collection('default')
                            ->imageHeight(350),
                        TextEntry::make('created_at')
                            ->label(__('app.created_at'))
                            ->dateTime()
                            ->placeholder('-')
                            ->inlineLabel(),
                        TextEntry::make('updated_at')
                            ->label(__('app.updated_at'))
                            ->dateTime()
                            ->placeholder('-')
                            ->inlineLabel(),
                    ]),

                Section::make(__('Questionnaires'))
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('questionnaires')
                            ->label(__('Questionnaires'))
                            ->inlineLabel()
                            ->getStateUsing(function ($record) {
                                $items = $record?->questionnaires ?? [];
                                // questionnaires is casted as AsCollection, so we need to convert it to array before checking if it's empty or not
                                if ($items instanceof Collection) {
                                    $items = $items->toArray();
                                }

                                if (empty($items)) {
                                    return '-';
                                }
                            
                                return collect($items)
                                    ->map(function ($item) {
                                        $question = $item['question'] ?? '-';
                                        $answerRaw = strtolower(trim($item['answer'] ?? ''));
                                        $remark = $item['remark'] ?? '-';
                                        $skipRemark = $item['skip_remark'] ?? '-';
                            
                                        // Map string to enum
                                        $answerEnum = match ($answerRaw) {
                                            'passed', 'ผ่าน' => AnswerType::PASSED,
                                            'not passed', 'ไม่ผ่าน' => AnswerType::NOT_PASSED,
                                            'missed', 'หมดเวลา' => AnswerType::MISS,
                                            'skip', 'ข้าม', 'skipped', 'ข้ามการตรวจ' => AnswerType::SKIP,
                                            default => null,
                                        };
                            
                                        $answerLabel = $answerEnum?->getLabel() ?? $item['answer'] ?? '-';
                            
                                        // Only show skip remark if answer is SKIP
                                        $skipRemarkHtml = $answerEnum === AnswerType::SKIP
                                            ? "<span style='color: #555;'>Skip Remark: {$skipRemark}</span><br>"
                                            : '';
                            
                                        return "<div style='margin-bottom: 6px;'>
                                                    <strong>{$question}</strong><br>
                                                    <span style='color: #555;'>Answer: {$answerLabel}</span><br>
                                                    <span style='color: #555;'>Remark: {$remark}</span><br>
                                                    {$skipRemarkHtml}
                                                </div>";
                                    })
                                    ->join('');
                            })                            
                            ->html(),
                    ]),
            ]);
    }
}
