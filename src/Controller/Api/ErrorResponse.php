<?php

namespace App\Controller\Api;

use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;

class ErrorResponse
{
    #[OA\Property(type: 'boolean', default: false)]
    public bool $success = false;

    #[OA\Property(type: 'array', items: new OA\Items(ref: new Model(type: Error::class)))]
    public array $errors = [];

    /**
     * ErrorResponse constructor.
     *
     * @param Error ...$errors
     */
    public function __construct(Error ...$errors)
    {
        $this->errors = $errors;
    }
}
