<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Operator = 'operator';
    case Viewer = 'viewer';

    public function isAdminLike(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Admin], true);
    }

    public function canExport(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Admin, self::Viewer], true);
    }
}
