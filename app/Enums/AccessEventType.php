<?php

namespace App\Enums;

enum AccessEventType: string
{
    case ACCESS_ALLOWED = 'access-allowed';
    case ACCESS_GRANTED = 'access-granted';
    case ACCESS_REJECTED = 'access-rejected';
    case ACCESS_DENIED = 'access-denied';
    case CHECK_IN = 'check-in';
    // client-start
    case CLIENT_START = 'client-start';

    public static function labels(): array
    {
        return [
            self::ACCESS_ALLOWED->value => __('enums.access_event_type.ACCESS_ALLOWED'),
            self::ACCESS_GRANTED->value => __('enums.access_event_type.ACCESS_GRANTED'),
            self::ACCESS_REJECTED->value => __('enums.access_event_type.ACCESS_REJECTED'),
            self::ACCESS_DENIED->value => __('enums.access_event_type.ACCESS_DENIED'),
            self::CHECK_IN->value => __('enums.access_event_type.CHECK_IN'),
            self::CLIENT_START->value => __('enums.access_event_type.CLIENT_START')
        ];
    }

    public static function colors(): array
    {
        return [
            self::ACCESS_ALLOWED->value => 'success',
            self::ACCESS_GRANTED->value => 'success',
            self::ACCESS_REJECTED->value => 'danger',
            self::ACCESS_DENIED->value => 'danger',
            self::CHECK_IN->value => 'primary',
            self::CLIENT_START->value => 'info'
        ];
    }

    public function label(): string
    {
        return self::labels()[$this->value] ?? 'Unknown';
    }
    
    public function color(): string
    {
        return self::colors()[$this->value] ?? 'default';
    }

    public static function getLabel(self $value): string
    {
        return self::labels()[$value->value] ?? 'Unknown';
    }

    public static function getColor(self $value): string
    {
        return self::colors()[$value->value] ?? 'Unknown';
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case) => [$case->value => self::getLabel($case)])->toArray();
    }
}
