<?php

namespace App\Filament\Resources\EmailCampaigns\Tables;

use App\Filament\Resources\EmailCampaigns\EmailCampaignResource;
use App\Jobs\DevOps\ProcessEmailCampaignJob;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Services\EmailCampaignService;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmailCampaignTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subject')
                    ->label(__('email-campaign.subject_column'))
                    ->searchable(),

                TextColumn::make('status')
                    ->label(__('email-campaign.status_column'))
                    ->badge()
                    ->colors([
                        'primary' => 'draft',
                        'warning' => ['pending', 'partial'],
                        'info' => 'sending',
                        'success' => 'completed',
                        'danger' => 'failed',
                    ])
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'completed' => __('email-campaign.completed_status'),
                            'partial' => __('email-campaign.partial_status'),
                            'sending' => __('email-campaign.sending_status'),
                            default => ucfirst($state ?? 'unknown'),
                        };
                    }),
                TextColumn::make('sent_count')
                    ->label(__('email-campaign.sent_count'))
                    ->formatStateUsing(fn($state) => number_format($state ?? 0))
                    ->color('success')
                    ->toggleable(),

                TextColumn::make('failed_count')
                    ->label(__('email-campaign.failed_count'))
                    ->formatStateUsing(fn($state) => number_format($state ?? 0))
                    ->color(fn($state) => ($state ?? 0) > 0 ? 'danger' : 'secondary')
                    ->toggleable(),

                TextColumn::make('progress')
                    ->label(__('email-campaign.progress'))
                    ->formatStateUsing(fn($state) => $state ? "{$state}%" : '0%')
                    ->color(
                        fn($state) => $state >= 100 ? 'success' : ($state >= 50 ? 'warning' : 'danger')
                    )
                    ->visible(fn($record) => $record && in_array($record->status, ['pending', 'sending', 'completed'])),

                TextColumn::make('total_recipients')
                    ->label(__('email-campaign.total_recipients'))
                    ->formatStateUsing(fn($state) => $state ? number_format($state) : '0'),

                TextColumn::make('created_at')
                    ->label(__('email-campaign.created_at_column'))
                    ->dateTime()
                    ->sortable(),

                SpatieMediaLibraryImageColumn::make('email-campaign_image')
                    ->label(__('app.image'))
                    ->collection('email_campaign_cover_images')
                    ->toggleable(),
            ])
            ->poll('10s') // Reduced frequency: 10s instead of 5s for better performance
            ->modifyQueryUsing(function (Builder $query) {
                // Batch refresh stats for active campaigns only when needed
                $activeCampaignIds = EmailCampaign::whereIn('status', ['pending', 'sending'])
                    ->where('updated_at', '<', now()->subMinutes(2)) // Only refresh if not updated in last 2 minutes
                    ->pluck('id')
                    ->take(5); // Reduced from 10 to 5 for better performance

                if ($activeCampaignIds->isNotEmpty()) {
                    // Use batch processing instead of individual calls
                    app(EmailCampaignService::class)->batchSyncCampaignStats($activeCampaignIds->toArray());
                }

                return $query;
            })
            ->recordActions([
                ViewAction::make()
                    ->url(fn($record) => EmailCampaignResource::getUrl('view', ['record' => $record])),

                EditAction::make()
                    ->visible(fn($record) => $record->status === 'draft'),

                Action::make('sendTest')
                    ->label(__('email-campaign.send_test_email'))
                    ->icon('heroicon-o-paper-airplane')
                    ->visible(function ($record) {
                        // Simple direct check - no complex caching needed
                        $emailService = app(EmailCampaignService::class);

                        return ! $emailService->isCompletelySuccessful($record->id);
                    })
                    ->schema([
                        TextInput::make('test_email')
                            ->email()
                            ->required()
                            ->label(__('email-campaign.test_email_address')),
                    ])
                    ->action(function ($record, array $data) {
                        app(EmailCampaignService::class)
                            ->sendTestEmail($record, $data['test_email']);
                        Notification::make()
                            ->title(__('email-campaign.test_email_sent'))
                            ->success()
                            ->send();
                    }),

                Action::make('refreshStats')
                    ->label(__('email-campaign.refresh_stats'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('secondary')
                    ->visible(fn($record) => in_array($record->status, ['pending', 'sending', 'completed', 'partial', 'failed']))
                    ->action(function ($record) {
                        $emailService = app(EmailCampaignService::class);
                        $stats = $emailService->refreshCampaignStats($record->id);
                        $emailService->syncCampaignStats($record->id);

                        Notification::make()
                            ->title(__('email-campaign.stats_refreshed'))
                            ->body(__('email-campaign.stats_summary', [
                                'sent' => number_format($stats['sent_count']),
                                'failed' => number_format($stats['failed_count']),
                                'progress' => $stats['progress'],
                            ]))
                            ->success()
                            ->send();
                    }),

                Action::make('sendBlast')
                    ->label(function ($record) {
                        // Simple direct check
                        $emailService = app(EmailCampaignService::class);
                        $hasFailedEmails = $emailService->hasFailedEmails($record->id);

                        if ($hasFailedEmails && in_array($record->status, ['partial', 'failed'])) {
                            return __('email-campaign.resend_failed');
                        }

                        return __('email-campaign.send');
                    })
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(function ($record) {
                        $emailService = app(EmailCampaignService::class);
                        $hasFailedEmails = $emailService->hasFailedEmails($record->id);

                        if ($hasFailedEmails && in_array($record->status, ['partial', 'failed'])) {
                            return __('email-campaign.resend_failed_confirm');
                        }

                        return __('email-campaign.send_confirm');
                    })
                    ->modalDescription(function ($record) {
                        // Simple direct check with single query when needed
                        $emailService = app(EmailCampaignService::class);
                        $hasFailedEmails = $emailService->hasFailedEmails($record->id);

                        if ($hasFailedEmails && in_array($record->status, ['partial', 'failed'])) {
                            // Only run this count query if we actually need it for the modal
                            $failedCount = EmailCampaignRecipient::where('email_campaign_id', $record->id)
                                ->where('status', 'failed')
                                ->count();

                            return __('email-campaign.resend_failed_desc', ['count' => number_format($failedCount)]);
                        }

                        return __('email-campaign.send_desc');
                    })
                    ->visible(function ($record) {
                        // Simple direct check
                        $emailService = app(EmailCampaignService::class);

                        // Hide if campaign is completely successful
                        if ($emailService->isCompletelySuccessful($record->id)) {
                            return false;
                        }

                        // Show for draft campaigns or campaigns with failed emails
                        return in_array($record->status, ['draft', 'failed', 'partial']);
                    })
                    ->action(function ($record) {
                        // Simple direct check
                        $emailService = app(EmailCampaignService::class);
                        $hasFailedEmails = $emailService->hasFailedEmails($record->id);

                        if ($hasFailedEmails && in_array($record->status, ['partial', 'failed'])) {
                            // Use smart resend for failed emails only
                            $emailService->resendFailedEmails($record);

                            Notification::make()
                                ->title(__('email-campaign.failed_emails_queued'))
                                ->body(__('email-campaign.only_failed_resent'))
                                ->success()
                                ->send();
                        } else {
                            // Regular send for draft campaigns
                            ProcessEmailCampaignJob::dispatch($record->id);
                            $record->update(['status' => 'pending']);

                            Notification::make()
                                ->title(__('email-campaign.campaign_queued'))
                                ->success()
                                ->send();
                        }
                    }),
            ]);
    }
}