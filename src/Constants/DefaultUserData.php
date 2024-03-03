<?php

declare(strict_types=1);

namespace App\Constants;

class DefaultUserData
{
    public const DEFAULT_EMAIL = 'test@test.com';
    public const DEFAULT_AUTH_CODE = '4321';

    public static function isDefaultEmail(string $email): bool
    {
        return self::DEFAULT_EMAIL === $email;
    }

    public static function isDefaultCode(string $code): bool
    {
        return self::DEFAULT_AUTH_CODE === $code;
    }
}
