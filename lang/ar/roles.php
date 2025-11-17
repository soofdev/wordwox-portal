<?php

return [
    // Role Management
    'Roles & Permissions' => 'الأدوار والصلاحيات',
    'manage_user_roles_and_permissions' => 'إدارة أدوار المستخدمين وصلاحياتهم',
    'Roles' => 'الأدوار',
    'Role' => 'الدور',
    'Role Name' => 'اسم الدور',
    'role_details' => 'تفاصيل الدور',
    'role_statistics' => 'إحصائيات الدور',
    'Create Role' => 'إنشاء دور',
    'create_new_role' => 'إنشاء دور جديد',
    'Edit Role' => 'تعديل الدور',
    'Update Role' => 'تحديث الدور',
    'delete_role' => 'حذف الدور',
    'back_to_roles' => 'العودة للأدوار',
    
    // Role Form
    'enter_role_name' => 'أدخل اسم الدور',
    'Description' => 'الوصف',
    'enter_role_description' => 'أدخل وصف الدور',
    'Optional' => 'اختياري',
    
    // Role Statistics
    'Total Permissions' => 'إجمالي الصلاحيات',
    'users_with_this_role' => 'المستخدمون بهذا الدور',
    'total_roles' => 'إجمالي الأدوار',
    'total_users_assigned' => 'إجمالي المستخدمين المعينين',
    'no_users_assigned' => 'لا يوجد مستخدمون معينون',
    'Users Assigned' => 'المستخدمون المعينون',
    'active_permissions' => 'الصلاحيات النشطة',
    
    // Permissions
    'Permissions' => 'الصلاحيات',
    'Permission' => 'الصلاحية',
    'toggle_permissions_for_role' => 'تبديل الصلاحيات لهذا الدور',
    'select_permissions_for_role' => 'اختر الصلاحيات لهذا الدور',
    'assign_permissions' => 'تعيين الصلاحيات',
    'selected_permissions' => 'الصلاحيات المحددة',
    'total_available' => 'المتاح الإجمالي',
    
    // Permission Groups
    'Members' => 'الأعضاء',
    'Memberships' => 'العضويات',
    'check_ins' => 'تسجيل الحضور',
    'settings_and_reports' => 'الإعدادات والتقارير',
    
    // Individual Permissions
    // Members
    'create members' => 'إنشاء الأعضاء',
    'view members' => 'عرض الأعضاء',
    'edit members' => 'تعديل الأعضاء',
    'delete members' => 'حذف الأعضاء',
    'check in members' => 'تسجيل حضور الأعضاء',
    
    // Memberships
    'create memberships' => 'إنشاء العضويات',
    'view memberships' => 'عرض العضويات',
    'edit memberships' => 'تعديل العضويات',
    'cancel memberships' => 'إلغاء العضويات',
    'transfer memberships' => 'نقل العضويات',
    'hold memberships' => 'تعليق العضويات',
    'upgrade memberships' => 'ترقية العضويات',
    'modify membership dates' => 'تعديل تواريخ العضوية',
    'modify membership limits' => 'تعديل حدود العضوية',
    'upcharge memberships' => 'رسوم إضافية للعضويات',
    
    // Check-ins
    'view check ins' => 'عرض تسجيل الحضور',
    
    // Settings & Reports
    'manage settings' => 'إدارة الإعدادات',
    'view reports' => 'عرض التقارير',
    
    // User Assignment
    'Users' => 'المستخدمون',
    'User' => 'المستخدم',
    'Add User' => 'إضافة مستخدم',
    'add_user_to_role' => 'إضافة مستخدم للدور',
    'assigned_users' => 'المستخدمون المعينون',
    'available_users' => 'المستخدمون المتاحون',
    'select_user' => 'اختيار مستخدم',
    'choose_user' => 'اختر مستخدماً...',
    'start_typing' => 'ابدأ الكتابة ...',
    'search_users' => 'البحث عن المستخدمين',
    'search_by_name_or_email' => 'البحث بالاسم أو البريد الإلكتروني...',
    'no_available_users' => 'لا يوجد مستخدمون متاحون للتعيين',
    'no_users_assigned_to_role' => 'لا يوجد مستخدمون معينون لهذا الدور',
    'all_users_assigned' => 'تم تعيين جميع المستخدمين لهذا الدور',
    'Remove' => 'إزالة',
    'total_items' => 'إجمالي :count عناصر',
    
    // Messages
    'role_created_successfully' => 'تم إنشاء الدور بنجاح!',
    'role_updated_successfully' => 'تم تحديث الدور بنجاح!',
    'role_deleted_successfully' => 'تم حذف الدور بنجاح!',
    'permission_added_successfully' => 'تم إضافة الصلاحية بنجاح!',
    'permission_removed_successfully' => 'تم إزالة الصلاحية بنجاح!',
    'user_assigned_successfully' => 'تم تعيين المستخدم للدور بنجاح!',
    'user_removed_successfully' => 'تم إزالة المستخدم من الدور بنجاح!',
    'failed_to_create_role' => 'فشل في إنشاء الدور',
    'failed_to_update_role' => 'فشل في تحديث الدور',
    'failed_to_delete_role' => 'فشل في حذف الدور',
    'failed_to_assign_user' => 'فشل في تعيين المستخدم',
    'failed_to_remove_user' => 'فشل في إزالة المستخدم',
    
    // Validation Messages
    'role_name_required' => 'اسم الدور مطلوب.',
    'role_name_unique' => 'اسم الدور هذا موجود بالفعل.',
    'role_name_max_length' => 'اسم الدور لا يمكن أن يتجاوز 255 حرفاً.',
    'role_description_max_length' => 'الوصف لا يمكن أن يتجاوز 500 حرف.',
    'select_user_to_assign' => 'يرجى اختيار مستخدم لتعيينه لهذا الدور.',
    'user_invalid' => 'المستخدم المحدد غير صحيح.',
    'user_not_available' => 'المستخدم المحدد غير متاح للتعيين.',
    'user_does_not_exist' => 'المستخدم المحدد غير موجود.',
    'user_already_assigned' => 'هذا المستخدم معين بالفعل لهذا الدور.',
    'user_already_assigned_with_role' => 'هذا المستخدم مُعيّن بالفعل لدور ":role".',
    
    // Empty States
    'no_roles_found' => 'لا توجد أدوار',
    'create_first_role' => 'أنشئ أول دور للبدء في إدارة الصلاحيات',
    'select_role_to_manage' => 'اختر دور',
    'choose_role_from_left' => 'اختر دور من اليسار لإدارة صلاحياته',
    
    // Actions
    'Save' => 'حفظ',
    'Saving...' => 'جاري الحفظ...',
    'Cancel' => 'إلغاء',
    'Edit' => 'تعديل',
    'Delete' => 'حذف',
    'Create' => 'إنشاء',
    'Creating...' => 'جاري الإنشاء...',
    'Update' => 'تحديث',
    'Updating...' => 'جاري التحديث...',
    'Actions' => 'الإجراءات',
    'Close' => 'إغلاق',
    
    // Status
    'Active' => 'نشط',
    'Inactive' => 'غير نشط',
    'Status' => 'الحالة',
    'Created' => 'تم الإنشاء',
    'created_date' => 'تاريخ الإنشاء',
];
