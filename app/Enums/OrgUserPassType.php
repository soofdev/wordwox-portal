<?php

namespace App\Enums;

enum OrgUserPassType: int
{
    case DROPIN = 2;
    case VISIT = 6;

    public function getLabel(): string
    {
        return match($this) {
            self::DROPIN => __('enums.org_user_pass_type.DROPIN'),
            self::VISIT => __('enums.org_user_pass_type.VISIT'),
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::VISIT => 'heroicon-s-user-group',
            self::DROPIN => 'heroicon-s-ticket',
        };
    }


    public static function getOptions(): array
    {
        return [
            self::VISIT->value => self::VISIT->getLabel(),
            self::DROPIN->value => self::DROPIN->getLabel(),
        ];
    }
}
