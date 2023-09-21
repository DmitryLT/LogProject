<?php

namespace App\Controller\Api;

use OpenApi\Attributes\Property as OAProperty;

class Error
{
    #[OAProperty(type: 'string', example: 'id')]
    public string $property_path;

    #[OAProperty(type: 'string', example: 'Сообщение об ошибке')]
    public string $message;

    #[OAProperty(type: 'string', example: 'Код ошибки, если есть')]
    public ?string $code;

    #[OAProperty(type: 'string', example: 'Детали')]
    public ?string $details;

    public function __construct(string $property_path, string $message, ?string $code = null, ?string $details = null)
    {
        $this->property_path = $property_path;
        $this->message = $message;
        $this->code = $code;
        $this->details = $details;
    }
}
