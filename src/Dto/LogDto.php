<?php

namespace App\Dto;

use App\Entity\Log;

class LogDto
{
    private function __construct(
        public string $serviceName,
        public string $description
    ) {
    }

    public static function buildFromEntity(Log $log): self
    {
        return new static(
            $log->getServiceName(),
            $log->getDescription()
        );
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
