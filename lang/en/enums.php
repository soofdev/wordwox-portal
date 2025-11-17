<?php

return [
    'org_user_plan_status' => [
        'None' => 'None',
        'Upcoming' => 'Upcoming',
        'Active' => 'Active',
        'Hold' => 'Hold',
        'Canceled' => 'Canceled',
        'Deleted' => 'Deleted',
        'Pending' => 'Pending',
        'Expired' => 'Expired',
        'ExpiredLimit' => 'Expired (Limit)',
        'Valid' => 'Valid',
        'Used' => 'Used',
    ],

    'org_user_plan_type' => [
        'MEMBERSHIP' => 'Membership',
        'DROPIN' => 'Drop-in',
        'PT' => 'Personal Training',
        'OPENGYM' => 'Open Gym',
        'PROGRAM' => 'Program',
    ],

    'org_user_pass_status' => [
        'None' => 'None',
        'Active' => 'Active',
        'Suspended' => 'Suspended',
        'Expired' => 'Expired',
        'Canceled' => 'Canceled',
        'Pending' => 'Pending',
        'Used' => 'Used',
        'Refunded' => 'Refunded',
    ],

    'org_user_pass_type' => [
        'DayPass' => 'Day Pass',
        'WeekPass' => 'Week Pass',
        'MonthPass' => 'Month Pass',
        'ClassPass' => 'Class Pass',
        'GuestPass' => 'Guest Pass',
        'TrialPass' => 'Trial Pass',
        'OpenGymPass' => 'Open Gym Pass',
        'PTPass' => 'Personal Training Pass',
    ],

    'note_type' => [
        'GENERAL' => 'General',
        'TRAINING' => 'Training',
        'HEALTH' => 'Health',
        'ACCOUNTING' => 'Accounting',
    ],

    'invoice_payment_status' => [
        'PENDING' => 'Pending',
        'PAID' => 'Paid',
        'REFUNDED' => 'Refunded',
        'CANCELED' => 'Canceled',
        'DELETED' => 'Deleted',
    ],

    'invoice_status' => [
        'PENDING' => 'Pending',
        'PAID' => 'Paid',
        'PARTIALLY_PAID' => 'Partially Paid',
        'REFUNDED' => 'Refunded',
        'PARTIALLY_REFUNDED' => 'Partially Refunded',
        'CANCELED' => 'Canceled',
        'FREE' => 'Free',
        'PENDING_POS' => 'Pending POS',
        'DELETED' => 'Deleted',
    ],

    'invoice_item_type' => [
        'membership' => 'Membership',
        'drop_in' => 'Drop-in',
        'hold' => 'Hold',
        'transfer' => 'Transfer',
        'level_promotion' => 'Level Promotion',
        'unknown' => 'Unknown',
    ],
];
