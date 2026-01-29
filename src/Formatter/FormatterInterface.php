<?php

declare(strict_types=1);

namespace Soleinjast\ValidationResponse\Formatter;

use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Interface for formatting validation violations into response arrays.
 */
interface FormatterInterface
{
    /**
     * Format validation violations into a response array.
     *
     * @param ConstraintViolationListInterface $violations The validation violations
     * @return array The formatted response data
     */
    public function format(ConstraintViolationListInterface $violations): array;
}