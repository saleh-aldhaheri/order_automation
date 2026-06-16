<?php

namespace App\Enums;

enum RolesEnum: string
{
    case SUPER_ADMIN = 'Super Admin';
    case ADMIN = 'Admin';
    case User = 'user';

    public static function adminPermissions()
    {
        return [
            PermissionsEnum::USER_VIEW,
            PermissionsEnum::USER_CREATE,
            PermissionsEnum::USER_UPDATE,
            PermissionsEnum::USER_DELETE,
            PermissionsEnum::SETTINGS_VIEW,
            PermissionsEnum::SETTINGS_UPDATE,
            PermissionsEnum::ROLE_VIEW,
            PermissionsEnum::ROLE_CREATE,
            PermissionsEnum::ROLE_UPDATE,
            PermissionsEnum::ROLE_DELETE,
            PermissionsEnum::PERMISSION_VIEW,
            PermissionsEnum::PERMISSION_CREATE,
            PermissionsEnum::PERMISSION_UPDATE,
            PermissionsEnum::PERMISSION_DELETE,
            PermissionsEnum::SEND_INVITATION,
        ];
    }

    public static function userPermissions()
    {
        return [];
    }

    public function middleware(): string
    {
        return 'role:'.$this->value;
    }
}
