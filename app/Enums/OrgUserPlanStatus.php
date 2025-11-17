<?php

namespace App\Enums;

enum OrgUserPlanStatus: int
{
    case None = 0;
    case Upcoming = 1;
    case Active = 2;
    case Hold = 3;
    case Canceled = 4;
    case Deleted = 5;
    case Pending = 6;
    case ExpiredLimit = 98;
    case Expired = 99;

    public function label(): string
    {
        return match($this) {
            self::None => __('enums.org_user_plan_status.None'),
            self::Upcoming => __('enums.org_user_plan_status.Upcoming'),
            self::Active => __('enums.org_user_plan_status.Active'),
            self::Hold => __('enums.org_user_plan_status.Hold'),
            self::Canceled => __('enums.org_user_plan_status.Canceled'),
            self::Deleted => __('enums.org_user_plan_status.Deleted'),
            self::Pending => __('enums.org_user_plan_status.Pending'),
            self::Expired => __('enums.org_user_plan_status.Expired'),
            self::ExpiredLimit => __('enums.org_user_plan_status.ExpiredLimit'),
        };
    }

    public static function getOrderedOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->name();
        }
        asort($options);
        return $options;
    }
    
    public function getColor(): string
    {
        return match($this) {
            self::None => 'gray',
            self::Active => 'success',
            self::Expired, self::ExpiredLimit, self::Deleted => 'danger',
            self::Hold => 'info',
            self::Upcoming => 'primary',
            self::Pending => 'warning',
            self::Canceled => 'warning',
        };
    }
    
    public function getBadge(): string
    {
        return \Illuminate\Support\Facades\Blade::render(
            '<x-filament::badge color="' . $this->getColor() . '">' . $this->label() . '</x-filament::badge>'
        );
    }
}