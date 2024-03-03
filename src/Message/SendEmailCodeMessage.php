<?php

declare(strict_types=1);

namespace App\Message;

use DateTimeImmutable;
use Exception;

class SendEmailCodeMessage
{
    private string $email;
    private int $code;
    private DateTimeImmutable $createdAt;
    private int $retryAttempt = 1;

    /**
     * @throws Exception
     */
    public function __construct(string $email)
    {
        $this->email = $email;
        $this->code = random_int(1000, 9999);
        $this->createdAt = new DateTimeImmutable();
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function incrementRetryAttempt(): void
    {
        $this->retryAttempt++;
    }

    public function getRetryAttempt(): int
    {
        return $this->retryAttempt;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
