<?php

return [
    'org_user_plan_status' => [
        'None' => 'لا شيء',
        'Upcoming' => 'قادم',
        'Active' => 'نشط',
        'Hold' => 'معلق',
        'Canceled' => 'ملغي',
        'Deleted' => 'محذوف',
        'Pending' => 'قيد الانتظار',
        'Expired' => 'منتهي الصلاحية',
        'ExpiredLimit' => 'منتهي الصلاحية (حد الجلسات)',
        'Valid' => 'صالح',
        'Used' => 'مستخدم',
    ],

    'org_user_plan_type' => [
        'MEMBERSHIP' => 'عضوية',
        'DROPIN' => 'زيارة واحدة',
        'PT' => 'تدريب شخصي',
        'OPENGYM' => 'صالة مفتوحة',
        'PROGRAM' => 'برنامج',
    ],

    'org_user_pass_status' => [
        'None' => 'لا شيء',
        'Active' => 'نشط',
        'Suspended' => 'معلق',
        'Expired' => 'منتهي الصلاحية',
        'Canceled' => 'ملغي',
        'Pending' => 'في الانتظار',
        'Used' => 'مستخدم',
        'Refunded' => 'مسترد',
    ],

    'org_user_pass_type' => [
        'DayPass' => 'تذكرة يومية',
        'WeekPass' => 'تذكرة أسبوعية',
        'MonthPass' => 'تذكرة شهرية',
        'ClassPass' => 'تذكرة حصص',
        'GuestPass' => 'تذكرة ضيف',
        'TrialPass' => 'تذكرة تجريبية',
        'OpenGymPass' => 'تذكرة صالة مفتوحة',
        'PTPass' => 'تذكرة تدريب شخصي',
    ],

    'note_type' => [
        'GENERAL' => 'عام',
        'TRAINING' => 'تدريب',
        'HEALTH' => 'صحة',
        'ACCOUNTING' => 'محاسبة',
    ],

    'invoice_payment_status' => [
        'PENDING' => 'قيد الانتظار',
        'PAID' => 'مدفوع',
        'REFUNDED' => 'مسترد',
        'CANCELED' => 'ملغي',
        'DELETED' => 'محذوف',
    ],

    'invoice_status' => [
        'PENDING' => 'قيد الانتظار',
        'PAID' => 'مدفوع',
        'PARTIALLY_PAID' => 'مدفوع جزئياً',
        'REFUNDED' => 'مسترد',
        'PARTIALLY_REFUNDED' => 'مسترد جزئياً',
        'CANCELED' => 'ملغي',
        'FREE' => 'مجاني',
        'PENDING_POS' => 'في انتظار نقاط البيع',
        'DELETED' => 'محذوف',
    ],

    'invoice_item_type' => [
        'membership' => 'عضوية',
        'drop_in' => 'زيارة واحدة',
        'hold' => 'تجميد',
        'transfer' => 'نقل',
        'level_promotion' => 'ترقية المستوى',
        'unknown' => 'غير معروف',
    ],
];
