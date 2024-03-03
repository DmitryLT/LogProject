<?php

namespace App\Dto;

class EmailGatewayResponseDTO
{
    private array $content;

    /**
     * @param array $content
     */
    public function __construct(array $content)
    {
        $this->content = $content;
    }


    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return !($this->content['errors'] ?? false);
    }

    /**
     * @return array
     */
    public function getContent(): array
    {
        return $this->content;
    }

    /**
     * @param array $content
     */
    public function setContent(array $content): void
    {
        $this->content = $content;
    }
}
