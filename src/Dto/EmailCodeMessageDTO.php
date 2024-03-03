<?php

namespace App\Dto;

class EmailCodeMessageDTO
{
    /**
     * @param string $email
     * @param int $code
     */
    public function __construct(
        private string $email,
        private int $code
    ) {
    }


    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return EmailCodeMessageDTO
     */
    public function setEmail(string $email): EmailCodeMessageDTO
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @param int $code
     * @return EmailCodeMessageDTO
     */
    public function setCode(int $code): EmailCodeMessageDTO
    {
        $this->code = $code;
        return $this;
    }
}
