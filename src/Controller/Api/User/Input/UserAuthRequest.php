<?php

namespace App\Controller\Api\User\Input;

use App\Traits\SafeLoadFieldsTrait;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

class UserAuthRequest
{
    use SafeLoadFieldsTrait;

    #[
        Assert\NotBlank,
        Assert\Regex(
            pattern: '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/',
            message: 'Email address is incorrect'
        ),
        Assert\Length(max: 255),
        OA\Property(type: 'string', example: 'dshametun@gmail.com')
    ]
    public string $email;

    protected function getSafeFields(): array
    {
        return ['email'];
    }

    public function getSafeFieldTypes(): array
    {
        return [];
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}