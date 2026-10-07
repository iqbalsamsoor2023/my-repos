<?php

namespace App\Filament\Resources\EmailCampaigns\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use App\Models\EmailCampaignRecipient;
use App\Filament\Resources\EmailCampaigns\EmailCampaignResource;
use App\Jobs\DevOps\ProcessEmailCampaignJob;
use App\Services\EmailCampaignService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewEmailCampaign extends ViewRecord
{
    protected static string $resource = EmailCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn() => $this->record->status === 'draft'),

            Action::make('sendTest')
                ->label(__('email-campaign.send_test_email'))
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->visible(function () {
                    $emailService = app(EmailCampaignService::class);

                    return ! $emailService->isCompletelySuccessful($this->record->id);
                })
                ->schema([
                    TextInput::make('test_email')
                        ->email()
                        ->required()
                        ->label(__('email-campaign.test_email_address')),
                ])
                ->action(function (array $data) {
                    app(EmailCampaignService::class)
                        ->sendTestEmail($this->record, $data['test_email']);
                    Notification::make()
                        ->title(__('email-campaign.test_email_sent'))
                        ->success()
                        ->send();
                }),

            Action::make('refreshStats')
                ->label(__('email-campaign.refresh_stats'))
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->visible(fn () => in_array($this->record->status, ['pending', 'sending', 'completed', 'partial', 'failed']))
                ->action(function () {
                    $emailService = app(EmailCampaignService::class);
                    $stats = $emailService->refreshCampaignStats($this->record->id);
                    $emailService->syncCampaignStats($this->record->id);

                    Notification::make()
                        ->title(__('email-campaign.stats_refreshed'))
                        ->body(__('email-campaign.stats_summary', [
                            'sent' => number_format($stats['sent_count']),
                            'failed' => number_format($stats['failed_count']),
                            'progress' => $stats['progress'],
                        ]))
                        ->success()
                        ->send();

                    // Refresh the record data
                    $this->refreshFormData([
                        'status',
                        'sent_count',
                        'failed_count',
                        'progress',
                        'total_recipients',
                    ]);
                }),

            Action::make('sendBlast')
                ->label(function () {
                    $emailService = app(EmailCampaignService::class);
                    $hasFailedEmails = $emailService->hasFailedEmails($this->record->id);

                    if ($hasFailedEmails && in_array($this->record->status, ['partial', 'failed'])) {
                        return __('email-campaign.resend_failed');
                    }

                    return __('email-campaign.send');
                })
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(function () {
                    $emailService = app(EmailCampaignService::class);
                    $hasFailedEmails = $emailService->hasFailedEmails($this->record->id);

                    if ($hasFailedEmails && in_array($this->record->status, ['partial', 'failed'])) {
                        return __('email-campaign.resend_failed_confirm');
                    }

                    return __('email-campaign.send_confirm');
                })
                ->modalDescription(function () {
                    $emailService = app(EmailCampaignService::class);
                    $hasFailedEmails = $emailService->hasFailedEmails($this->record->id);

                    if ($hasFailedEmails && in_array($this->record->status, ['partial', 'failed'])) {
                        $failedCount = EmailCampaignRecipient::where('email_campaign_id', $this->record->id)
                            ->where('status', 'failed')
                            ->count();

                        return __('email-campaign.resend_failed_desc', ['count' => number_format($failedCount)]);
                    }

                    return __('email-campaign.send_desc');
                })
                ->visible(function () {
                    $emailService = app(EmailCampaignService::class);

                    if ($emailService->isCompletelySuccessful($this->record->id)) {
                        return false;
                    }

                    return in_array($this->record->status, ['draft', 'failed', 'partial']);
                })
                ->action(function () {
                    $emailService = app(EmailCampaignService::class);
                    $hasFailedEmails = $emailService->hasFailedEmails($this->record->id);

                    if ($hasFailedEmails && in_array($this->record->status, ['partial', 'failed'])) {
                        $emailService->resendFailedEmails($this->record);

                        Notification::make()
                            ->title(__('email-campaign.failed_emails_queued'))
                            ->body(__('email-campaign.only_failed_resent'))
                            ->success()
                            ->send();
                    } else {
                        ProcessEmailCampaignJob::dispatch($this->record->id);
                        $this->record->update(['status' => 'pending']);

                        Notification::make()
                            ->title(__('email-campaign.campaign_queued'))
                            ->success()
                            ->send();
                    }

                    // Refresh the record data
                    $this->refreshFormData(['status']);
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Format the data for better display
        if (isset($data['target_filters']) && is_string($data['target_filters'])) {
            $data['target_filters'] = json_decode($data['target_filters'], true);
        }

        return $data;
    }
}
