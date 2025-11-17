<?php

namespace App\Enums;

enum OrgUserPlanHoldStatus: int
{
    case None = 0;
    case Upcoming = 1;
    case Active = 2;
    case Canceled = 98;
    case Expired = 99;

    /**
     * Get the human-readable label for the status
     */
    public function label(): string
    {
        return match($this) {
            self::None => 'None',
            self::Upcoming => 'Upcoming',
            self::Active => 'Active',
            self::Canceled => 'Canceled',
            self::Expired => 'Expired',
        };
    }

    /**
     * Get the color for display purposes
     */
    public function color(): string
    {
        return match($this) {
            self::None => 'gray',
            self::Upcoming => 'gray',
            self::Active => 'green',
            self::Canceled => 'red',
            self::Expired => 'red',
        };
    }
}
