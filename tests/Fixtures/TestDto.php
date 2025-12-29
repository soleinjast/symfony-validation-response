<?php

declare(strict_types=1);

namespace Soleinjast\ValidationResponse\Tests\Fixtures;

use Symfony\Component\Validator\Constraints as Assert;

final class TestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Name is required')]
        #[Assert\Length(min: 3, minMessage: 'Name must be at least 3 characters')]
        public string $name = '',

        #[Assert\PositiveOrZero(message: 'Price must be positive or zero')]
        public int $price = 0,
    ) {}
}