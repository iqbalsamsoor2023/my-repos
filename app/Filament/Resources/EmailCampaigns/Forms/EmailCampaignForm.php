<?php

namespace App\Filament\Resources\EmailCampaigns\Forms;

use App\Enums\Residence\MoobanType;
use App\Models\Erp\ThailandDistrict;
use App\Models\Erp\ThailandProvince;
use App\Models\Erp\ThailandSubDistrict;
use App\Models\Residence;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\App;

class EmailCampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('email-campaign.details'))
                    ->description(__('email-campaign.details_desc'))
                    ->schema([
                        TextInput::make('subject')
                            ->label(__('email-campaign.subject'))
                            ->required()
                            ->maxLength(255),

                        Textarea::make('message')
                            ->label(__('email-campaign.message_body'))
                            ->rows(8)
                            ->required(),

                        SpatieMediaLibraryFileUpload::make('cover_image')
                            ->label(__('app.cover_image'))
                            ->collection('email_campaign_cover_images')
                            ->disk('cos')
                            ->image()
                            ->imageAspectRatio('3:2')
                            ->automaticallyCropImagesToAspectRatio()
                            ->automaticallyResizeImagesMode('cover')
                            ->automaticallyResizeImagesToWidth('600')
                            ->automaticallyResizeImagesToHeight('400')
                            ->maxSize(2048)
                            ->openable(true)
                            ->hint(__('email-campaign.image_optional_hint'))
                            ->helperText(__('email-campaign.image_will_be_cropped')),
                    ]),

                Section::make(__('email-campaign.recipients'))
                    ->description(__('email-campaign.recipients_desc'))
                    ->schema([
                        Toggle::make('send_to_all')
                            ->label(__('email-campaign.send_to_all_owners_tenants'))
                            ->helperText(__('email-campaign.send_to_all_help'))
                            ->reactive()
                            ->default(false),

                        Fieldset::make(__('email-campaign.filters'))
                            ->visible(fn(callable $get) => ! $get('send_to_all'))
                            ->schema([
                                Select::make('target_filters.provinces')
                                    ->label(__('email-campaign.provinces'))
                                    ->options(
                                        ThailandProvince::all()
                                            ->mapWithKeys(fn($province) => [
                                                $province->id => App::getLocale() === 'th'
                                                    ? $province->name_in_thai
                                                    : $province->name_in_english,
                                            ])
                                    )
                                    ->searchable()
                                    ->multiple()
                                    ->reactive()
                                    ->afterStateUpdated(fn(callable $set) => $set('target_filters.districts', [])),

                                Select::make('target_filters.districts')
                                    ->label(__('email-campaign.districts'))
                                    ->options(function (callable $get) {
                                        return ThailandDistrict::whereIn('province_id', $get('target_filters.provinces') ?? [])
                                            ->get()
                                            ->mapWithKeys(fn($district) => [
                                                $district->id => App::getLocale() === 'th'
                                                    ? $district->name_in_thai
                                                    : $district->name_in_english,
                                            ]);
                                    })
                                    ->searchable()
                                    ->multiple()
                                    ->reactive()
                                    ->afterStateUpdated(fn(callable $set) => $set('target_filters.sub_districts', [])),

                                Select::make('target_filters.sub_districts')
                                    ->label(__('email-campaign.sub_districts'))
                                    ->options(function (callable $get) {
                                        return ThailandSubDistrict::whereIn('district_id', $get('target_filters.districts') ?? [])
                                            ->get()
                                            ->mapWithKeys(fn($subDistrict) => [
                                                $subDistrict->id => App::getLocale() === 'th'
                                                    ? $subDistrict->name_in_thai
                                                    : $subDistrict->name_in_english,
                                            ]);
                                    })
                                    ->searchable()
                                    ->multiple()
                                    ->reactive(),

                                Select::make('target_filters.residence_types')
                                    ->label(__('email-campaign.residence_types'))
                                    ->options(MoobanType::casesToOptions())
                                    ->multiple()
                                    ->preload()
                                    ->searchable(),

                                Select::make('target_filters.residences')
                                    ->label(__('email-campaign.residences'))
                                    ->options(
                                        fn(callable $get) => Residence::whereIn('subdistrict_id', $get('target_filters.sub_districts') ?? [])
                                            ->pluck('name', 'id')
                                    )
                                    ->multiple()
                                    ->searchable(),
                            ]),
                    ]),
            ]);
    }
}
