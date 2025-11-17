<?php

namespace App\Enums;

enum OrgUserPassStatus: int
{
    case None = 0;
    case Active = 1;
    case Suspended = 2;
    case Expired = 3;
    case Canceled = 4;
    case Pending = 5;
    case Used = 6;
    case Refunded = 7;

    public function label(): string
    {
        return match($this) {
            self::None => __('enums.org_user_pass_status.None'),
            self::Active => __('enums.org_user_pass_status.Active'),
            self::Suspended => __('enums.org_user_pass_status.Suspended'),
            self::Expired => __('enums.org_user_pass_status.Expired'),
            self::Canceled => __('enums.org_user_pass_status.Canceled'),
            self::Pending => __('enums.org_user_pass_status.Pending'),
            self::Used => __('enums.org_user_pass_status.Used'),
            self::Refunded => __('enums.org_user_pass_status.Refunded'),
        
    };
}

    public function getColor(): string
    {
        return match($this) {
            self::None => 'gray',
            self::Active => 'success',
            self::Suspended => 'warning',
            self::Expired => 'danger',
            self::Canceled => 'danger',
            self::Pending => 'primary',
            self::Used => 'secondary',
            self::Refunded => 'info',
        };
    }

    public function getBadgeColor(): string
    {
        return match($this) {
            self::None => 'zinc',
            self::Active => 'green',
            self::Suspended => 'yellow',
            self::Expired => 'red',
            self::Canceled => 'red',
            self::Pending => 'blue',
            self::Used => 'gray',
            self::Refunded => 'purple',
        };
    }

    public static function getOptions(): array
    {
        return [
            self::None->value => self::None->label(),
            self::Active->value => self::Active->label(),
            self::Suspended->value => self::Suspended->label(),
            self::Expired->value => self::Expired->label(),
            self::Canceled->value => self::Canceled->label(),
            self::Pending->value => self::Pending->label(),
            self::Used->value => self::Used->label(),
            self::Refunded->value => self::Refunded->label(),
        ];
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }

   }
