<?php

namespace App\Filament\Resources\UnitUsers\Pages;

use function Sentry\captureException;
use App\Enums\UnitUser\ApprovalStatusType;
use App\Filament\Resources\UnitUsers\UnitUserResource;
use App\Mail\UserRegistered as MailUserRegistered;
use App\Models\User;
use Exception;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Mail;

class CreateUnitUser extends CreateRecord
{
    protected static string $resource = UnitUserResource::class;

    public function getTitle(): string
    {
        return __('menu.create_resident');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $userID = $this->data['user_id'];
        $userData = User::where('id', $userID)->first();
        if ($userData) {
            if (empty($userData->email_verified_at)) {
                if ($userData->email) {
                    try {
                        Mail::to($userData->email)->send(new MailUserRegistered($userData));
                    } catch (Exception $ex) {
                        captureException($ex);
                    }
                }
            }

            User::where('id', $userID)->update([
                'email_verified_at' => null,
            ]);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['approval_status'] ??= ApprovalStatusType::APPROVED->value;

        return $data;
    }
}
