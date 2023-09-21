<?php

namespace App\Traits;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

trait SafeLoadFieldsTrait
{
    /**
     * @return string[]
     * @example ['email', 'password']
     */
    abstract protected function getSafeFields(): array;

    /**
     * @return string[]
     * @example ['email', 'password']
     */
    abstract public function getSafeFieldTypes(): array;

    public function loadFromRawJsonRequest(Request $request): void
    {
        $this->loadFromArray(json_decode($request->getContent(), true));
    }

    public function loadFromRequest(Request $request): void
    {
        $this->loadFromBag($request->request);
    }

    public function loadFromAttributes(Request $request): void
    {
        $this->loadFromBag($request->attributes);
    }

    public function loadFromQuery(Request $request): void
    {
        $this->loadFromBag($request->query);
    }

    /**
     * @param array|null $input
     *
     * @return void
     */
    public function loadFromArray(?array $input): void
    {
        if (empty($input)) {
            return;
        }

        foreach ($this->getSafeFields() as $field) {
            $requestField = $this->camelCaseToSnakeCase($field);
            if (isset($input[$requestField])) {
                $this->{$field} = $this->loadRecursive($field, $input[$requestField]);
            }
        }
    }

    /**
     * @param string $className
     * @return bool
     */
    private function hasSafeLoadFieldsTraitInClass(string $className): bool
    {
        do {
            $hasTrait = in_array(SafeLoadFieldsTrait::class, class_uses($className), true);
            $className = get_parent_class($className);
        } while (!$hasTrait && $className);

        return $hasTrait;
    }

    private function loadRecursive(string $field, $data)
    {
        if (!isset($this->getSafeFieldTypes()[$field])) {
            return $data;
        }

        // Recursive load objects if they support this trait
        $class = $this->getSafeFieldTypes()[$field]->getClass();

        $createAndLoadObject = function ($className, $data) {
            /** @var SafeLoadFieldsTrait $object */
            $object = new $className();
            if ($this->hasSafeLoadFieldsTraitInClass($className)) {
                $object->loadFromArray($data);

                return $object;
            }

            return null;
        };

        $createdObject = null;
        if ($this->getSafeFieldTypes()[$field]->isArray()) {
            if (is_array($data)) {
                $createdObject = array_map(function ($item) use ($createAndLoadObject, $class) {
                    return $createAndLoadObject($class, $item);
                }, $data);
            }
        } else {
            $createdObject = $createAndLoadObject($class, $data);
        }

        return $createdObject;
    }

    /**
     * @param ParameterBag $bag
     *
     * @return void
     */
    private function loadFromBag(ParameterBag $bag): void
    {
        foreach ($this->getSafeFields() as $field) {
            $requestField = $this->camelCaseToSnakeCase($field);
            if ($bag->has($requestField)) {
                $this->{$field} = $this->loadRecursive($field, $bag->get($requestField));
            }
        }
    }

    /**
     * @param string $fieldName
     *
     * @return string
     */
    private function camelCaseToSnakeCase(string $fieldName): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $fieldName));
    }
}
