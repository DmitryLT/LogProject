<?php

namespace App\Service;

use App\Constants\RedisCacheDBs;
use App\Dto\EmailCodeMessageDTO;
use Redis;

class EmailCodeCacheService
{
    private const EMAIL_CODE_SECONDS_TTL = 300;

    public function __construct(
        private Redis $redis,
    ) {
    }

    public function clearAllDBs(): void
    {
        foreach (RedisCacheDBs::ALL_EMAIL_CODE_HASH_DB as $dbIdx) {
            $this->redis->select($dbIdx);
            $this->redis->flushDB();
        }
    }

    public function setEmailCode(EmailCodeMessageDTO $message): void
    {
        $this->redis->select(RedisCacheDBs::EMAIL_CODE_HASH_DB_IDX);
        $this->redis->set($message->getEmail(), $message->getCode());
        $this->redis->expire($message->getEmail(), self::EMAIL_CODE_SECONDS_TTL);
    }

    public function isCodeExist(string $phone): bool
    {
        $this->redis->select(RedisCacheDBs::EMAIL_CODE_HASH_DB_IDX);
        return $this->redis->exists($phone) > 0;
    }

    public function isCodeRight(string $phone, string $code): bool
    {
        $this->redis->select(RedisCacheDBs::EMAIL_CODE_HASH_DB_IDX);
        $cachedCode = $this->redis->get($phone);
        return $code == $cachedCode;
    }

    public function getCode(string $email): string
    {
        $this->redis->select(RedisCacheDBs::EMAIL_CODE_HASH_DB_IDX);
        return $this->redis->get($email);
    }
}
