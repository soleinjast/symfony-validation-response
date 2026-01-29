<?php

declare(strict_types=1);

namespace Soleinjast\ValidationResponse\Formatter;

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
class SimpleFormatter implements FormatterInterface
{
    public function format(ConstraintViolationListInterface $violations): array
    {
        $errors = [];
        foreach ($violations as $violation) {
            $propertyPath = $violation->getPropertyPath();
            $errors[$propertyPath][] = $violation->getMessage();
        }
        return ['errors' => $errors];
    }
}