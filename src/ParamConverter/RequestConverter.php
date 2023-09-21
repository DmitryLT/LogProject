<?php

namespace App\ParamConverter;

use App\Traits\SafeLoadFieldsTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class RequestConverter
 *
 * @package App\ParamConverter
 */
class RequestConverter implements ParamConverterInterface
{
    public function __construct(
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * @param Request        $request
     * @param ParamConverter $configuration
     *
     * @return bool
     */
    public function apply(Request $request, ParamConverter $configuration)
    {
        $violations = new ConstraintViolationList();

        if ($request->isMethod('POST') && !$this->validateJson($request->getContent())) {
            $lastError = json_last_error_msg();
            $violations->add($this->createViolation("Invalid JSON in the request body: '$lastError'"));
            $request->attributes->set('validationErrors', $violations);

            return false;
        }

        $class = $configuration->getClass();
        try {
            /** @var SafeLoadFieldsTrait $requestObject */
            $requestObject = new $class();
            $requestObject->loadFromAttributes($request);
            $requestObject->loadFromRawJsonRequest($request);
            $requestObject->loadFromRequest($request);
            $requestObject->loadFromQuery($request);
            $violations->addAll($this->validate($requestObject));
        } catch (\TypeError $e) {
            $violations->add($this->createTypeErrorViolation($e->getMessage()));
            $request->attributes->set('validationErrors', $violations);

            return false;
        }

        $request->attributes->set($configuration->getName(), $requestObject);
        $request->attributes->set('validationErrors', $violations);

        return true;
    }

    /**
     * @param ParamConverter $configuration
     *
     * @return bool
     */
    public function supports(ParamConverter $configuration)
    {
        if (empty($configuration->getClass())) {
            return false;
        }

        $safeFieldTraits = [SafeLoadFieldsTrait::class];
        $usedSafeFieldTraits = array_intersect($safeFieldTraits, class_uses($configuration->getClass()));

        return count($usedSafeFieldTraits) > 0;
    }

    private function validateJson(mixed $json): bool
    {
        return empty($json) || is_string($json) && is_array(json_decode($json, true));
    }

    private function validate(mixed $value): ConstraintViolationListInterface
    {
        $violations = new ConstraintViolationList();

        if (is_object($value)) {
            $violations->addAll($this->validator->validate($value));
            foreach (get_object_vars($value) as $fieldValue) {
                if (is_iterable($fieldValue)) {
                    $violations->addAll($this->validate($fieldValue));
                }
            }
        }

        if (is_iterable($value)) {
            foreach ($value as $valueItem) {
                $violations->addAll($this->validate($valueItem));
            }
        }

        return $violations;
    }

    private function createTypeErrorViolation(string $errorText): ConstraintViolation
    {
        $type = preg_replace('/^.*Cannot assign ([^\s]*) to.*$/i', '$1', $errorText);
        $type = $type == $errorText ? null : $type;
        $property = preg_replace('/^.*\$([^\s]*).*$/', '$1', $errorText);
        $property = $property == $errorText ? null : $property;
        $resultText = $errorText;
        if ($type && $property) {
            $resultText = "Type of property '$property' should be '$type'";
        }

        return $this->createViolation($resultText, $property);
    }

    private function createViolation(string|\Stringable $message, ?string $propertyPath = null): ConstraintViolation
    {
        return new ConstraintViolation($message, null, [], null, $propertyPath, null);
    }
}
