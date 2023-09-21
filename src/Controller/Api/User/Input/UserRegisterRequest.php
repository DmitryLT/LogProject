<?php

declare(strict_types=1);

namespace App\Controller\Api\User\Input;

use App\Traits\SafeLoadFieldsTrait;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

final class UserRegisterRequest
{
    use SafeLoadFieldsTrait;

    #[
        Assert\NotBlank,
        Assert\Regex(
            pattern: '/(^8|7|\+7)((\d{10})|(\s\(\d{3}\)\s\d{3}\s\d{2}\s\d{2}))/',
            message: 'Phone number is incorrect'
        ),
        Assert\Length(max: 15),
        OA\Property(type: 'string', example: '+79042565656')
    ]
    public string $phone;

    #[
        Assert\NotBlank,
        Assert\Type(type: 'string'),
        Assert\Length(max: 64),
        OA\Property(type: 'string', example: 'Alex')
    ]
    public string $name;

    #[
        Assert\Email(normalizer: 'trim'),
        Assert\Length(max: 64),
        OA\Property(type: 'string', example: 'test@email.ru', nullable: true)
    ]
    public ?string $email = null;

    protected function getSafeFields(): array
    {
        return ['phone','name', 'cityId', 'email', 'birthdate'];
    }

    public function getSafeFieldTypes(): array
    {
        return [];
    }

    /**
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }
}
