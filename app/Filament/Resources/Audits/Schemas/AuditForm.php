<?php

namespace App\Filament\Resources\Audits\Schemas;

use App\Enums\Audit\EventTypeEnum;
use App\Models\User;
use App\Services\GeoLocationService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use hisorange\BrowserDetect\Parser as Browser;
use Illuminate\Support\HtmlString;

class AuditForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('app.audit'))
                    ->description(__('app.audit_details'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('event')
                            ->label(__('app.event'))
                            ->required()
                            ->options(EventTypeEnum::options())
                            ->native(false),
                        Select::make('user_id')
                            ->label(__('app.name'))
                            ->options(function (callable $get) {
                                return User::whereId($get('user_id'))->pluck('name', 'id');
                            })
                            ->hint(fn ($record) => $record?->userable?->email ?? '-')
                            ->hintColor('gray')
                            ->disabled(),
                        TextInput::make('auditable_type')
                            ->label(__('app.auditable_type')),
                        TextInput::make('auditable_id')
                            ->label(__('app.auditable_id')),
                        TextEntry::make('old_values')
                            ->label(__('Old Values'))
                            ->state(function ($record) {
                                $decoded = $record->old_values;

                                // Ensure the data is an array
                                if (! is_array($decoded)) {
                                    return new HtmlString('<em>Invalid data</em>');
                                }

                                // Format each key => value pair
                                $content = collect($decoded)
                                    ->map(function ($value, $key) {
                                        if (is_bool($value)) {
                                            $formatted = $value ? 'true' : 'false';
                                        } elseif (is_null($value)) {
                                            $formatted = 'null';
                                        } else {
                                            $formatted = e($value); // Escape HTML
                                        }

                                        return "<strong>{$key}:</strong> {$formatted}";
                                    })
                                    ->implode('<br>'); // Join with line breaks

                                return new HtmlString($content);
                            })
                            ->visible(fn ($record) => ! empty($record?->old_values)),
                        TextEntry::make('new_values')
                            ->label(__('New Values'))
                            ->state(function ($record) {
                                $decoded = $record->new_values;

                                // Ensure the data is an array
                                if (! is_array($decoded)) {
                                    return new HtmlString('<em>Invalid data</em>');
                                }

                                // Format each key => value pair
                                $content = collect($decoded)
                                    ->map(function ($value, $key) {
                                        if (is_bool($value)) {
                                            $formatted = $value ? 'true' : 'false';
                                        } elseif (is_null($value)) {
                                            $formatted = 'null';
                                        } else {
                                            $formatted = e($value); // Escape HTML
                                        }

                                        return "<strong>{$key}:</strong> {$formatted}";
                                    })
                                    ->implode('<br>'); // Join with line breaks

                                return new HtmlString($content);
                            })
                            ->visible(fn ($record) => ! empty($record?->new_values)),
                        TextEntry::make('ip_address')
                            ->label(__('checkpoint.location'))
                            ->state(function ($record) {
                                // $record is the Audit model instance
                                $ip = $record->ip_address ?? null;

                                // Check for empty
                                if (empty($ip)) {
                                    return 'Unknown';
                                }

                                return GeoLocationService::getLocationFromIp($ip);
                            }),
                        TextEntry::make('user_agent')
                            ->label(__('app.device_info'))
                            ->state(function ($record) {

                                $user_agent = $record->user_agent ?? null;

                                if (empty($user_agent)) {
                                    return 'Unknown';
                                }

                                $parser = new Browser;
                                $result = $parser->parse($user_agent);

                                return $result->platformName().' - '.$result->browserName();
                            }),
                    ]),
            ]);
    }
}
