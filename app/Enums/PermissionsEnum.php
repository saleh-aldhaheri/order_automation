<?php

namespace App\Enums;

enum PermissionsEnum: string
{
    case USER_VIEW = 'user:view';
    case USER_CREATE = 'user:create';
    case USER_UPDATE = 'user:update';
    case USER_DELETE = 'user:delete';

    case SETTINGS_VIEW = 'settings:view';
    case SETTINGS_UPDATE = 'settings:update';

    case ROLE_VIEW = 'role:view';
    case ROLE_CREATE = 'role:create';
    case ROLE_UPDATE = 'role:update';
    case ROLE_DELETE = 'role:delete';

    case PERMISSION_VIEW = 'permission:view';
    case PERMISSION_CREATE = 'permission:create';
    case PERMISSION_UPDATE = 'permission:update';
    case PERMISSION_DELETE = 'permission:delete';

    case PROVIDER_VIEW = 'provider:view';
    case PROVIDER_CONNECT = 'provider:connect';
    case PROVIDER_REFRESH = 'provider:refresh';

    case SEND_INVITATION = 'send:invitation';

    case EXTERNAL_SYSTEM_VIEW = 'external_system:view';
    case EXTERNAL_SYSTEM_CREATE = 'external_system:create';
    case EXTERNAL_SYSTEM_UPDATE = 'external_system:update';
    case EXTERNAL_SYSTEM_DELETE = 'external_system:delete';
    case EXTERNAL_SYSTEM_ROTATE_SECRET = 'external_system:rotate_secret';
    case EXTERNAL_SYSTEM_GENERATE_TOKEN = 'external_system:generate_token';
    case EXTERNAL_SYSTEM_REVOKE_TOKEN = 'external_system:revoke_token';

    public function middleware(): string
    {
        return 'permission:' . $this->value;
    }
}
 