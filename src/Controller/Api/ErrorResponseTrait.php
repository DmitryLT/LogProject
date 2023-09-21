<?php

namespace App\Controller\Api;

use FOS\RestBundle\View\View;
use JetBrains\PhpStorm\Pure;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface as ValidationErrors;

trait ErrorResponseTrait
{
    #[Pure]
    private function createError(
        string $propertyPath,
        string $message,
        ?string $code = null,
        ?string $details = null
    ): Error {
        return new Error($propertyPath, $message, $code, $details);
    }

    #[Pure]
    private function createErrorResponseDTO(
        string $propertyPath,
        string $message,
        ?string $code = null,
        ?string $details = null
    ): ErrorResponse {
        return new ErrorResponse($this->createError($propertyPath, $message, $code, $details));
    }

    private function createErrorResponse(
        int $responseCode,
        string $propertyPath,
        string $message,
        ?string $code = null,
        ?string $details = null
    ): View {
        return View::create($this->createErrorResponseDTO($propertyPath, $message, $code, $details), $responseCode);
    }

    /**
     * @param int              $httpCode
     * @param ValidationErrors $validationErrors
     *
     * @return View
     */
    private function createValidationErrorResponse(int $httpCode, ValidationErrors $validationErrors): View
    {
        $errors = [];
        foreach ($validationErrors as $error) {
            /** @var ConstraintViolationInterface $error */
            $errors[] = $this->createError($error->getPropertyPath(), $error->getMessage());
        }
        return View::create(new ErrorResponse(...$errors), $httpCode);
    }
}
