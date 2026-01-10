<?php

declare(strict_types=1);

namespace Soleinjast\ValidationResponse\Formatter;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 *  Simple formatter that returns a flat array of field => message pairs.
 *  Example output:
 * {
 *     "errors": {
 *         "name": "This field is required",
 *         "email": "Invalid email format"
 *     }
 * }
 */
class SimpleNestedFormatter implements FormatterInterface
{
    public function format(ConstraintViolationListInterface $violations): array
    {
        $usedKeys = [];
        $errors = [];
        foreach ($violations as $violation) {
            $propertyPath = $violation->getPropertyPath();
            $propertyPaths = sprintf('[%s]', $propertyPath);
            if (str_contains($propertyPath, '.')) {
                $propertyPaths = explode('.', $propertyPath);
                $propertyPaths = array_map(static fn (string $path) => sprintf('[%s]', $path), $propertyPaths);
                $propertyPaths = implode('', $propertyPaths);
            }

            if (!isset($usedKeys[$propertyPaths])) {
                $usedKeys[$propertyPaths] = -1;
            }
            $usedKeys[$propertyPaths]++;

            $accessor = PropertyAccess::createPropertyAccessor();
            $accessor->setValue($errors, "{$propertyPaths}[{$usedKeys[$propertyPaths]}]", $violation->getMessage());
        }

        return ['errors' => $errors];
    }
}
