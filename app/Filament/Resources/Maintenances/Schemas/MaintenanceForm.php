<?php

namespace App\Filament\Resources\Maintenances\Schemas;

use App\Enums\Maintenance\MaintenanceStatus;
use App\Enums\Residence\MoobanType;
use App\Filament\Resources\Maintenances\Pages\CreateMaintenance;
use App\Filament\Resources\Maintenances\Pages\EditMaintenance;
use App\Filament\Resources\PrivateMaintenances\Pages\CreatePrivateMaintenance;
use App\Filament\Resources\PrivateMaintenances\Pages\EditPrivateMaintenance;
use App\Filament\Resources\PrivateMaintenances\Pages\ViewPrivateMaintenance;
use App\Filament\Resources\PublicMaintenances\Pages\CreatePublicMaintenance;
use App\Filament\Resources\PublicMaintenances\Pages\EditPublicMaintenance;
use App\Filament\Resources\PublicMaintenances\Pages\ViewPublicMaintenance;
use App\Filament\Resources\Units\RelationManagers\MaintenancesRelationManager as PrivateMaintenancesRelationManager;
use App\Forms\Components\Maintenance\Comment;
use App\Http\Controllers\CommentController;
use App\Http\Requests\Maintenance\StoreCommentRequest;
use App\Models\ClaimableTitle;
use App\Models\Residence;
use App\Models\ResidenceAmenity;
use App\Models\ResidenceAmenityOption;
use App\Models\Unit;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Throwable;

class MaintenanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('maintenance.maintenance'))
                    ->description(__('maintenance.maintenance_detail'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Fieldset::make(__('maintenance.mooban_residence_and_facility_amenity'))
                            ->columnSpanFull()
                            ->visible(
                                fn(Component $livewire): bool => $livewire instanceof CreatePublicMaintenance ||
                                    $livewire instanceof EditPublicMaintenance ||
                                    $livewire instanceof ViewPublicMaintenance ||
                                    $livewire instanceof CreatePrivateMaintenance ||
                                    $livewire instanceof EditPrivateMaintenance ||
                                    $livewire instanceof ViewPrivateMaintenance ||
                                    $livewire instanceof PrivateMaintenancesRelationManager
                            )
                            ->schema([
                                Select::make('residence_id')
                                    ->hidden(fn(Component $livewire): bool => $livewire instanceof PrivateMaintenancesRelationManager)
                                    ->label(__('app.mooban_or_residence'))
                                    ->options(function (Component $livewire) {
                                        if ($livewire instanceof CreatePublicMaintenance || $livewire instanceof CreatePrivateMaintenance) {
                                            return list_create_residences();
                                        }

                                        return list_residences();
                                    })
                                    ->reactive()
                                    ->searchable()
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('maintainable_id', null);
                                        $set('amenity', null);
                                    })
                                    ->required(),
                                Select::make('maintainable_id')
                                    ->label(__('maintenance.facilities_or_amenities'))
                                    ->visible(
                                        fn(Component $livewire): bool => $livewire instanceof CreatePublicMaintenance ||
                                            $livewire instanceof EditPublicMaintenance ||
                                            $livewire instanceof ViewPublicMaintenance
                                    )
                                    ->options(function (callable $get, Component $livewire) {
                                        $locale = App::getLocale();

                                        $isEditMode = $livewire instanceof EditPublicMaintenance
                                            || $livewire instanceof ViewPublicMaintenance;

                                        $isCreateMode = $livewire instanceof CreatePublicMaintenance;

                                        $query = ResidenceAmenity::with(['facilityAndAmenity', 'residenceAmenityOptions'])
                                            ->where('residence_id', $get('residence_id'))
                                            ->where('is_claimable', true);

                                        if ($isCreateMode) {
                                            $query->where('is_active', true)
                                                ->whereHas('facilityAndAmenity', function ($q) {
                                                    $q->where('name', '!=', 'Others');
                                                });
                                        }

                                        if (!$isEditMode) {
                                            $query->where('is_active', true);
                                        }

                                        $amenities = $query->get();

                                        return $amenities->flatMap(function ($amenity) use ($locale) {
                                            $facility = $amenity->facilityAndAmenity;

                                            $facilityName = $facility
                                                ? ($locale === 'th' ? $facility->name_in_thai : $facility->name)
                                                : '-';

                                            if ($amenity->residenceAmenityOptions->isNotEmpty()) {
                                                return $amenity->residenceAmenityOptions->mapWithKeys(function ($option) use ($facilityName, $locale) {
                                                    $optionName = $locale === 'th'
                                                        ? $option->name_in_thai
                                                        : $option->name;

                                                    return ["option-{$option->id}" => "{$facilityName} ({$optionName})"];
                                                });
                                            }

                                            return ["amenity-{$amenity->id}" => $facilityName];
                                        })
                                            ->toArray();
                                    })
                                    ->reactive()
                                    ->preload()
                                    ->searchable()
                                    ->required()
                                    ->disabled(
                                        fn(Component $livewire): bool =>
                                        $livewire instanceof EditMaintenance ||
                                            $livewire instanceof EditPublicMaintenance ||
                                            $livewire instanceof EditPrivateMaintenance
                                    )
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        if (Str::startsWith($state, 'option-')) {
                                            $set('maintainable_type', ResidenceAmenityOption::class);
                                        } elseif (Str::startsWith($state, 'amenity-')) {
                                            $set('maintainable_type', ResidenceAmenity::class);
                                        } else {
                                            $set('maintainable_type', null);
                                        }

                                        $set('claimable_title_id', null);
                                    }),
                                Select::make('claimable_title_id')
                                    ->label(__('maintenance.claimable_title'))
                                    ->options(function (callable $get) {
                                        $maintainableId = $get('maintainable_id');
                                        $maintainableType = $get('maintainable_type');

                                        if (!$maintainableId || !$maintainableType) {
                                            return [];
                                        }

                                        // Parse the ID from the prefixed value
                                        $parsedId = Str::of($maintainableId)->after('-')->toInteger();

                                        // Get facility_and_amenity_id
                                        $facilityAndAmenityId = null;

                                        if ($maintainableType === ResidenceAmenity::class) {
                                            $facilityAndAmenityId = ResidenceAmenity::where('id', $parsedId)->value('facility_and_amenity_id');
                                        }

                                        if ($maintainableType === ResidenceAmenityOption::class) {
                                            $facilityAndAmenityId = ResidenceAmenityOption::query()
                                                ->where('residence_amenity_options.id', $parsedId)
                                                ->join('residence_amenity', 'residence_amenity.id', '=', 'residence_amenity_options.residence_amenity_id')
                                                ->value('residence_amenity.facility_and_amenity_id');
                                        }

                                        if (!$facilityAndAmenityId) {
                                            return [];
                                        }

                                        return ClaimableTitle::query()
                                            ->join('claimable_title_facility_and_amenity as pivot', 'pivot.claimable_title_id', '=', 'claimable_titles.id')
                                            ->where('pivot.facility_and_amenity_id', $facilityAndAmenityId)
                                            ->pluck(
                                                App::getLocale() === 'th' ? 'claimable_titles.name_in_thai' : 'claimable_titles.name',
                                                'claimable_titles.id'
                                            )
                                            ->toArray();
                                    })
                                    ->visible(function (callable $get) {
                                        $maintainableId = $get('maintainable_id');
                                        $maintainableType = $get('maintainable_type');

                                        if (!$maintainableId || !$maintainableType) {
                                            return false;
                                        }

                                        $parsedId = Str::of($maintainableId)->after('-')->toInteger();

                                        $facilityAndAmenityId = null;

                                        if ($maintainableType === ResidenceAmenity::class) {
                                            $facilityAndAmenityId = ResidenceAmenity::where('id', $parsedId)->value('facility_and_amenity_id');
                                        }

                                        if ($maintainableType === ResidenceAmenityOption::class) {
                                            $facilityAndAmenityId = ResidenceAmenityOption::query()
                                                ->where('residence_amenity_options.id', $parsedId)
                                                ->join('residence_amenity', 'residence_amenity.id', '=', 'residence_amenity_options.residence_amenity_id')
                                                ->value('residence_amenity.facility_and_amenity_id');
                                        }

                                        return $facilityAndAmenityId && DB::table('claimable_title_facility_and_amenity')
                                            ->where('facility_and_amenity_id', $facilityAndAmenityId)
                                            ->exists();
                                    })
                                    ->required(function (callable $get) {
                                        $maintainableId = $get('maintainable_id');
                                        $maintainableType = $get('maintainable_type');

                                        if (!$maintainableId || !$maintainableType) {
                                            return false;
                                        }

                                        $parsedId = Str::of($maintainableId)->after('-')->toInteger();

                                        $facilityAndAmenityId = null;

                                        if ($maintainableType === \App\Models\ResidenceAmenity::class) {
                                            $facilityAndAmenityId = \App\Models\ResidenceAmenity::where('id', $parsedId)->value('facility_and_amenity_id');
                                        }

                                        if ($maintainableType === \App\Models\ResidenceAmenityOption::class) {
                                            $facilityAndAmenityId = \App\Models\ResidenceAmenityOption::query()
                                                ->where('residence_amenity_options.id', $parsedId)
                                                ->join('residence_amenity', 'residence_amenity.id', '=', 'residence_amenity_options.residence_amenity_id')
                                                ->value('residence_amenity.facility_and_amenity_id');
                                        }

                                        return $facilityAndAmenityId && \Illuminate\Support\Facades\DB::table('claimable_title_facility_and_amenity')
                                            ->where('facility_and_amenity_id', $facilityAndAmenityId)
                                            ->exists();
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->reactive(),
                                Hidden::make('maintainable_type'),
                                Select::make('maintainable_id')
                                    ->label(__('unit.unit_number'))
                                    ->visible(
                                        fn(Component $livewire): bool => $livewire instanceof CreatePrivateMaintenance ||
                                            $livewire instanceof EditPrivateMaintenance ||
                                            $livewire instanceof ViewPrivateMaintenance
                                    )
                                    ->options(function (callable $get) {
                                        return Unit::where('residence_id', $get('residence_id'))->pluck('unit_number', 'id');
                                    })
                                    ->searchable()
                                    ->required()
                                    ->reactive(),
                                Select::make('private_claim_category_id')
                                    ->label('Private Claim Category')
                                    ->options(function (callable $get, $state) {
                                        $residenceId = $get('residence_id');

                                        if (!$residenceId) {
                                            return [];
                                        }

                                        $locale = app()->getLocale();

                                        $categories = \App\Models\PrivateClaimCategory::whereHas(
                                            'privateClaimItems.visibilitySettings',
                                            function ($q) use ($residenceId) {
                                                $q->where('residence_id', $residenceId)
                                                    ->where('is_enabled', true);
                                            }
                                        )->orderBy('id')->get();

                                        $options = $categories->mapWithKeys(fn($category) => [
                                            $category->id => $locale === 'th' ? $category->name_th : $category->name
                                        ])->toArray();

                                        $residence = \App\Models\Residence::with('warrantySetting')->find($residenceId);

                                        $hasOtherOption =
                                            $residence &&
                                            $residence->mooban_type != MoobanType::PUBLIC->value &&
                                            optional($residence->warrantySetting)->has_other_option;

                                        if ($hasOtherOption || $state === 'others') {
                                            $options['others'] = $locale === 'th' ? 'อื่นๆ' : 'Others';
                                        }

                                        return $options;
                                    })

                                    // ✅ when form loads (edit / create)
                                    ->afterStateHydrated(function (callable $set, callable $get, $state, $context) {

                                        // ✅ ONLY apply for edit/view
                                        if (in_array($context, ['edit', 'view']) && empty($state)) {
                                            $set('private_claim_category_id', 'others');
                                        }

                                        // your existing logic (derive from item)
                                        if ($get('private_claim_item_id')) {
                                            $item = \App\Models\PrivateClaimItem::find($get('private_claim_item_id'));

                                            if ($item) {
                                                $set('private_claim_category_id', $item->private_claim_category_id);
                                            }
                                        }
                                    })

                                    // ✅ SINGLE afterStateUpdated (merged logic)
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        if (empty($state)) {
                                            $set('private_claim_category_id', 'others');
                                        }

                                        $set('private_claim_item_id', null);
                                        $set('private_claim_item_title_id', null);

                                        if ($state !== 'others') {
                                            $set('other_private_claim_item', null);
                                        }
                                    })
                                    ->dehydrateStateUsing(fn($state) => $state === 'others' ? null : $state)
                                    ->searchable()
                                    ->reactive()
                                    ->required(),
                                Select::make('private_claim_item_id')
                                    ->label('Private Claim Item')
                                    // ✅ HIDE when category = others
                                    ->visible(fn($get) => $get('private_claim_category_id') !== 'others')
                                    ->options(function (callable $get) {
                                        $categoryId = $get('private_claim_category_id');
                                        $locale = app()->getLocale();

                                        $items = [];

                                        if ($categoryId && $categoryId !== 'others') {
                                            $items = \App\Models\PrivateClaimItem::where('private_claim_category_id', $categoryId)
                                                ->pluck($locale === 'th' ? 'name_th' : 'name', 'id')
                                                ->toArray();
                                        }

                                        $items['others'] = 'Others';

                                        return $items;
                                    })

                                    // ❌ IMPORTANT: remove default if you only want it for edit, not create
                                    // ->default('others')

                                    // ✅ only for edit/view (same as category)
                                    ->afterStateHydrated(function ($state, callable $set, $context) {
                                        if (in_array($context, ['edit', 'view']) && empty($state)) {
                                            $set('private_claim_item_id', 'others');
                                        }
                                    })
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        if (empty($state)) {
                                            $set('private_claim_item_id', 'others');
                                        }

                                        $set('private_claim_item_title_id', null);

                                        if ($state !== 'others') {
                                            $set('other_private_claim_item', null);
                                        }
                                    })
                                    ->getOptionLabelUsing(function ($value) {
                                        if ($value === 'others') return 'Others';

                                        return \App\Models\PrivateClaimItem::find($value)
                                            ?->{app()->getLocale() === 'th' ? 'name_th' : 'name'};
                                    })
                                    ->dehydrateStateUsing(fn($state) => $state === 'others' ? null : $state)
                                    ->searchable()
                                    ->preload()
                                    ->reactive(),
                                TextInput::make('other_private_claim_item')
                                    ->label('Other Private Claim')
                                    ->visible(
                                        fn($get) =>
                                        $get('private_claim_category_id') === 'others' ||
                                            $get('private_claim_item_id') === 'others'
                                    )
                                    ->required(
                                        fn($get) =>
                                        $get('private_claim_category_id') === 'others' ||
                                            $get('private_claim_item_id') === 'others'
                                    )
                                    ->maxLength(255),
                                Select::make('private_claim_item_title_id')
                                    ->label(__('maintenance.private_claim_item_title'))
                                    ->options(function (callable $get) {
                                        $itemId = $get('private_claim_item_id');

                                        if (!$itemId || $itemId === 'others') {
                                            return [];
                                        }

                                        return \App\Models\PrivateClaimItemTitle::query()
                                            ->where('private_claim_item_id', $itemId)
                                            ->pluck(
                                                app()->getLocale() === 'th' ? 'option_name_th' : 'option_name',
                                                'id'
                                            );
                                    })
                                    ->visible(
                                        fn($get) =>
                                        $get('private_claim_category_id') !== 'others' &&
                                            $get('private_claim_item_id') !== 'others'
                                    )
                                    ->required(
                                        fn($get) =>
                                        $get('private_claim_category_id') !== 'others' &&
                                            $get('private_claim_item_id') !== 'others'
                                    )
                                    ->searchable(),

                                //  Select::make('amenity')
                                //     ->label(__('maintenance.amenity'))
                                //     ->visible(
                                //         fn (Component $livewire): bool => $livewire instanceof CreatePrivateMaintenance ||
                                //             $livewire instanceof EditPrivateMaintenance ||
                                //             $livewire instanceof ViewPrivateMaintenance ||
                                //             $livewire instanceof PrivateMaintenancesRelationManager
                                //     )
                                //     ->options(function (callable $get, Component $livewire) {
                                //         $residence_id = $get('residence_id');

                                //         if (isset($livewire->mountedTableAction) == true && $livewire->mountedTableAction == 'create') {
                                //             $residence_id = $livewire->ownerRecord->residence_id;
                                //         }

                                //         $amenities = [];
                                //         $amenities = Amenity::where('residence_id', $residence_id)->where('amenity_name', '!=', 'Others')->pluck('amenity_name', 'id')->toArray();
                                //         $amenities += ['Others' => 'Others'];

                                //         return $amenities;
                                //     })
                                // ->reactive()
                                // ->preload()
                                // ->searchable()
                                // ->required(),

                                TextInput::make('amenity_name')
                                    ->label(__('maintenance.amenity_name'))
                                    ->maxLength(255)
                                    ->visible(fn($get) => $get('amenity') == 'Others')
                                    ->reactive()
                                    ->required(),
                            ]),

                        Fieldset::make(__('app.details'))
                            ->columns(2)
                            ->columnSpanFull()
                            ->schema([
                                Textarea::make('issue_description')
                                    ->label(__('maintenance.issue_description')),
                                DateTimePicker::make('appointment_datetime')
                                    ->label(__('maintenance.appointment_datetime'))
                                    ->native(false)
                                    ->seconds(false)
                                    ->rules(
                                        fn($record) =>
                                        $record
                                            ? [] // update → no validation
                                            : ['after_or_equal:now'] // create → validate future
                                    ),
                                SpatieMediaLibraryFileUpload::make('image')
                                    ->label(__('app.image'))
                                    ->collection('maintenance_images')
                                    ->customProperties(['type' => 'maintenance'])
                                    ->disk('cos')
                                    ->multiple()
                                    ->maxFiles(6)
                                    ->openable(true)
                                    ->downloadable(true)
                                    ->reorderable(true),
                                Radio::make('status')
                                    ->label(__('maintenance.status'))
                                    ->hidden(
                                        fn(Component $livewire): bool => $livewire instanceof CreateMaintenance ||
                                            $livewire instanceof CreatePublicMaintenance ||
                                            $livewire instanceof CreatePrivateMaintenance ||
                                            $livewire instanceof PrivateMaintenancesRelationManager
                                    )
                                    ->options([
                                        MaintenanceStatus::PENDING->value => __('app.' . strtolower(MaintenanceStatus::PENDING->name)),
                                        MaintenanceStatus::IN_PROGRESS->value => __('app.' . strtolower(MaintenanceStatus::IN_PROGRESS->name)),
                                        MaintenanceStatus::COMPLETE->value => __('app.completed'),
                                    ])
                                    ->reactive()
                                    ->columns(3)
                                    ->required(),

                                Fieldset::make(__('Completed Details'))
                                    ->columnSpanFull()
                                    ->columns(3)
                                    ->visible(fn($get) => $get('status') == MaintenanceStatus::COMPLETE->value)
                                    ->schema([
                                        DateTimePicker::make('completion_datetime')
                                            ->label(__('maintenance.completion_datetime'))
                                            ->required(fn(Component $livewire): bool => $livewire instanceof
                                                $livewire instanceof EditPrivateMaintenance ||
                                                $livewire instanceof ViewPrivateMaintenance ||
                                                $livewire instanceof PrivateMaintenancesRelationManager
                                                )
                                            ->native(false)
                                            ->seconds(false),
                                        Textarea::make('completed_remark')
                                            ->label(__('app.remark')),
                                        SpatieMediaLibraryFileUpload::make('completed_image')
                                            ->label(__('app.image'))
                                            ->collection('maintenance_completed_images')
                                            ->customProperties(['type' => 'maintenance_completed'])
                                            ->disk('cos')
                                            ->multiple()
                                            ->maxFiles(5)
                                            ->openable(true)
                                            ->downloadable(true)
                                            ->reorderable(true),
                                    ]),

                                Fieldset::make(__('app.reporter_details'))
                                    ->columnSpanFull()
                                    ->hidden(
                                        fn(Component $livewire): bool => $livewire instanceof CreateMaintenance ||
                                            $livewire instanceof CreatePublicMaintenance ||
                                            $livewire instanceof CreatePrivateMaintenance ||
                                            $livewire instanceof PrivateMaintenancesRelationManager
                                    )
                                    ->schema([
                                        Select::make('reported_by')
                                            ->label(__('app.reported_by'))
                                            ->relationship('reportedBy', 'name')
                                            ->searchable()
                                            ->disabled(),
                                        Select::make('contact_number')
                                            ->label(__('app.phone_number'))
                                            ->options(function (callable $get) {
                                                return User::whereId($get('reported_by'))->pluck('phone_no');
                                            })
                                            ->selectablePlaceholder(false)
                                            ->disabled(),
                                    ]),

                                Fieldset::make(__('maintenance.verification_status'))
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->visible(function ($livewire, $get) {
                                        if ($get('is_verified') != NULL) {
                                            return false;
                                        }

                                        if (auth()->user()->hasRole(['Property Management'])) {
                                            $residence = Residence::with('warrantySetting')->where('property_management_user_id', auth()->user()->id)->first();

                                            return $residence?->warrantySetting?->has_verification ?? false;
                                        }

                                        return true;
                                    })
                                    ->disabled(fn ($get) => $get('status') != MaintenanceStatus::COMPLETE->value)
                                    ->schema([
                                        Textarea::make('verification_description')
                                            ->label(__('maintenance.verification_description')),
                                        Toggle::make('is_verified')
                                            ->label(__('maintenance.verification_status'))
                                            ->inline(false),
                                        SpatieMediaLibraryFileUpload::make('verification_image')
                                            ->label(__('app.image'))
                                            ->required(fn($get) => $get('is_verified') == true)
                                            ->collection('maintenance_verification_image')
                                            ->customProperties(['type' => 'maintenance_verification'])
                                            ->disk('cos')
                                            ->openable(true)
                                            ->downloadable(true),
                                        // Rating::make('rating')
                                        //     ->label(__('maintenance.rating'))
                                        //     ->translateLabel()
                                        //     ->size(7)
                                        //     ->clearable()
                                        //     ->clearIconColor('red')
                                        //     ->clearIconTooltip('Clear')
                                        //     ->options([
                                        //         'Bad',
                                        //         'Acceptable',
                                        //         'Good',
                                        //         'Very Good',
                                        //         'Excellent',
                                        //     ]),
                                    ]),

                                Fieldset::make(__('maintenance.comment_section'))
                                    ->columnSpanFull()
                                    ->columns(1)
                                    ->schema([
                                        Comment::make('comment')
                                            ->label('')
                                            ->dehydrated(false)
                                            ->registerActions([
                                                Action::make('send')
                                                    ->icon('heroicon-m-paper-airplane')
                                                    ->action(function (Set $set, $get, $state, $livewire) {
                                                        $commentRequest = new StoreCommentRequest();
                                                        $commentRequest->merge([
                                                            'content' => $state,
                                                            'user_id' => auth()->user()->id,
                                                            'redirect' => false,
                                                        ]);
                                                        try {
                                                            (new CommentController)->store($commentRequest, $get('id'));
                                                        } catch (Throwable $th) {
                                                            throw ValidationException::withMessages([
                                                                'data.comment' => 'Something went wrong with your request',
                                                            ]);
                                                        }

                                                        $set('comment', '');
                                                        $livewire->dispatch('comment-added');
                                                    }),
                                            ])
                                    ])
                                    ->visible(fn(Component $livewire): bool => $livewire instanceof ViewPrivateMaintenance),
                            ]),
                    ])
            ]);
    }
}
