<?php

namespace App\Enums;

enum AuditLogAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
}
