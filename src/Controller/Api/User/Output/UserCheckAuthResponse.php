<?php

namespace App\Controller\Api\User\Output;

use JsonSerializable;
use OpenApi\Attributes as OA;

class UserCheckAuthResponse implements JsonSerializable
{
    #[OA\Property(description: 'Auth token', type: 'string')]
    public string $token;

    #[OA\Property(description: 'Date when token will be expired', type: 'string')]
    public string $tokenExpiredAt;

    /**
     * @param string $token
     * @param string $tokenExpiredAt
     */
    public function __construct(string $token, string $tokenExpiredAt)
    {
        $this->token = $token;
        $this->tokenExpiredAt = $tokenExpiredAt;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
