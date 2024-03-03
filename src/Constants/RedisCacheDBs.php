<?php

declare(strict_types=1);

namespace App\Constants;

class RedisCacheDBs
{
    public const EMAIL_CODE_HASH_DB_IDX = 15;
    public const RELATED_PRODUCTS_DB_IDX = 14;

    public const ALL_DBS = [
        self::EMAIL_CODE_HASH_DB_IDX,
        self::RELATED_PRODUCTS_DB_IDX
    ];

    public const ALL_EMAIL_CODE_HASH_DB = [
        self::EMAIL_CODE_HASH_DB_IDX
    ];
}
