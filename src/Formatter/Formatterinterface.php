<?php

declare(strict_types=1);

namespace Soleinjast\ValidationResponse\Formatter;
/**
 * Interface for formatting validation violations into response arrays.
 */
interface Formatterinterface
{
    /**
     * Format validation violations into a response array.
     *
     * @param \Symfony\Component\Validator\ConstraintViolationListInterface $violations The validation violations
     * @return array The formatted response data
     */
    public function format(\Symfony\Component\Validator\ConstraintViolationListInterface $violations): array;
}