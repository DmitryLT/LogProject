<?php

declare(strict_types=1);

namespace App\Controller\Api\User\Input;

use App\Traits\SafeLoadFieldsTrait;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

final class UserCheckAuthRequest
{
    use SafeLoadFieldsTrait;

    #[
        Assert\NotBlank,
        Assert\Regex(
            pattern: '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/',
            message: 'Email is incorrect'
        ),
        Assert\Length(max: 64),
        OA\Property(type: 'string', example: 'dshametun@gmail.com')
    ]
    public string $email;

    #[
        Assert\NotBlank,
        Assert\Length(max:5),
        OA\Property(type: 'string', example: '1234')
    ]
    public string $code;

    protected function getSafeFields(): array
    {
        return ['email', 'code'];
    }

    public function getSafeFieldTypes(): array
    {
        return [];
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
