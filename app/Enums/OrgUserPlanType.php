<?php

namespace App\Enums;

/**
 * OrgUserPlanType Enum
 * 
 * This enum provides plan type labels and icons, but NOT colors.
 * For UI badges, always use 'gray' color - this enum does not have color methods.
 * Use the icon() method for visual differentiation between plan types.
 */
enum OrgUserPlanType: int
{
    case MEMBERSHIP = 1;
    case DROPIN = 2;
    case PT = 3;
    case OPENGYM = 4;
    case PROGRAM = 5;

    public function getLabel(): string
    {
        return match($this) {
            self::MEMBERSHIP => __('enums.org_user_plan_type.MEMBERSHIP'),
            self::DROPIN => __('enums.org_user_plan_type.DROPIN'),
            self::PT => __('enums.org_user_plan_type.PT'),
            self::OPENGYM => __('enums.org_user_plan_type.OPENGYM'),
            self::PROGRAM => __('enums.org_user_plan_type.PROGRAM'),
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::MEMBERSHIP => 'heroicon-s-user-group',
            self::DROPIN => 'heroicon-s-ticket',
            self::PT => 'heroicon-s-academic-cap',
            self::OPENGYM => 'heroicon-s-building-storefront',
            self::PROGRAM => 'heroicon-s-clipboard-document-list',
        };
    }


    public static function getOptions(): array
    {
        return [
            self::MEMBERSHIP->value => self::MEMBERSHIP->getLabel(),
            self::DROPIN->value => self::DROPIN->getLabel(),
            self::PT->value => self::PT->getLabel(),
            self::OPENGYM->value => self::OPENGYM->getLabel(),
            self::PROGRAM->value => self::PROGRAM->getLabel(),
        ];
    }
}
