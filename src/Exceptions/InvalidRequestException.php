<?php

declare(strict_types=1);

namespace App\Exceptions;

use JetBrains\PhpStorm\Pure;
use Symfony\Component\Validator\ConstraintViolationListInterface as ValidationErrors;

class InvalidRequestException extends \RuntimeException
{
    /** @var ValidationErrors|null */
    private ?ValidationErrors $validationErrors;
    private string $fieldName;

    #[Pure]
    public function __construct(string $message, ?string $fieldName = null, ?ValidationErrors $validationErrors = null)
    {
        parent::__construct($message);
        $this->validationErrors = $validationErrors;
        $this->fieldName = $fieldName ?? '*';
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    /**
     * @return ValidationErrors|null
     */
    public function getValidationErrors(): ?ValidationErrors
    {
        return $this->validationErrors;
    }
}
