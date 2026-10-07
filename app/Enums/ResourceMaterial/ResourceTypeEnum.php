<?php

namespace App\Enums\ResourceMaterial;

enum ResourceTypeEnum: string
{
    case USER_TUTORIAL = 'user_tutorial';
    case PRODUCT_OPERATION_GUIDE = 'product_operation_guide';
    case PRIVACY_POLICY = 'privacy_policy';
    case TERMS_OF_SERVICE = 'terms_of_service';

    public function label(): string
    {
        return match ($this) {
            self::USER_TUTORIAL => __('User Tutorial'),
            self::PRODUCT_OPERATION_GUIDE => __('Product Operation Guide'),
            self::PRIVACY_POLICY => __('Privacy Policy'),
            self::TERMS_OF_SERVICE => __('Terms of Service'),
        };
    }
}
