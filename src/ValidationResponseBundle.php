<?php

declare(strict_types=1);

namespace Soleinjast\ValidationResponse;

use Override;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * ValidationResponse Bundle
 *
 * Provides clean and customizable validation error responses for Symfony APIs.
 */
final class ValidationResponseBundle extends Bundle
{
    #[Override]
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}