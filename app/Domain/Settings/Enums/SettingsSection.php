<?php

namespace App\Domain\Settings\Enums;

enum SettingsSection: string
{
    case Appearance = 'appearance';
    case Reading = 'reading';
    case Privacy = 'privacy';
    case Notifications = 'notifications';
}
