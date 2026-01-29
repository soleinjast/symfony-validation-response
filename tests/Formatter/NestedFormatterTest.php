<?php

declare(strict_types=1);

namespace Soleinjast\ValidationResponse\Tests\Formatter;

use PHPUnit\Framework\TestCase;
use Soleinjast\ValidationResponse\Formatter\NestedFormatter;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

final class NestedFormatterTest extends TestCase
{
    private readonly NestedFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new NestedFormatter();
    }

    public function testFormatEmptyViolations(): void
    {
        $violations = new ConstraintViolationList();
        $result = $this->formatter->format($violations);

        $this->assertEmpty($result['errors']);
    }

    public function testFormatSingleViolation(): void
    {
        $violation = new ConstraintViolation(
            'This field is required',
            'This field is required',
            [],
            null,
            'name',
            ''
        );

        $violations = new ConstraintViolationList([$violation]);
        $result = $this->formatter->format($violations);

        $expected = [
            'errors' => [
                'name' => ['This field is required'],
            ],
        ];

        $this->assertSame($expected, $result);
    }

    public function testFormatNestedPropertyPath(): void
    {
        $violation = new ConstraintViolation(
            'Invalid city',
            'Invalid city',
            [],
            null,
            'address.city',
            ''
        );

        $violations = new ConstraintViolationList([$violation]);
        $result = $this->formatter->format($violations);

        $expected = [
            'errors' => [
                'address' => [
                    'city' => ['Invalid city'],
                ],
            ],
        ];

        $this->assertSame($expected, $result);
    }

    public function testFormatMultipleViolationsAcrossNestedPaths(): void
    {
        $violation1 = new ConstraintViolation(
            'Name is required',
            'Name is required',
            [],
            null,
            'customer.name',
            ''
        );
        $violation2 = new ConstraintViolation(
            'City is required',
            'City is required',
            [],
            null,
            'customer.address.city',
            ''
        );
        $violation3 = new ConstraintViolation(
            'Postal code is invalid',
            'Postal code is invalid',
            [],
            null,
            'customer.address.zipCode',
            ''
        );
        $violation4 = new ConstraintViolation(
            'City is invalid',
            'City is invalid',
            [],
            null,
            'customer.address.city',
            ''
        );

        $violations = new ConstraintViolationList([
            $violation1,
            $violation2,
            $violation3,
            $violation4,
        ]);

        $result = $this->formatter->format($violations);

        $expected = [
            'errors' => [
                'customer' => [
                    'name' => ['Name is required'],
                    'address' => [
                        'city' => ['City is required', 'City is invalid'],
                        'zipCode' => ['Postal code is invalid'],
                    ],
                ],
            ],
        ];

        $this->assertSame($expected, $result);
    }

    public function testFormatBracketedIndexes(): void
    {
        $violation1 = new ConstraintViolation(
            'Name is required',
            'Name is required',
            [],
            null,
            'items[0].name',
            ''
        );
        $violation2 = new ConstraintViolation(
            'Price is invalid',
            'Price is invalid',
            [],
            null,
            'items[0].price',
            ''
        );
        $violation3 = new ConstraintViolation(
            'City is invalid',
            'City is invalid',
            [],
            null,
            'items[1].address.city',
            ''
        );

        $violations = new ConstraintViolationList([
            $violation1,
            $violation2,
            $violation3,
        ]);

        $result = $this->formatter->format($violations);

        $expected = [
            'errors' => [
                'items' => [
                    '0' => [
                        'name' => ['Name is required'],
                        'price' => ['Price is invalid'],
                    ],
                    '1' => [
                        'address' => [
                            'city' => ['City is invalid'],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertSame($expected, $result);
    }

    public function testFormatMixedLeafAndNestedPaths(): void
    {
        $violation1 = new ConstraintViolation(
            'Item is invalid',
            'Item is invalid',
            [],
            null,
            'items[0]',
            ''
        );
        $violation2 = new ConstraintViolation(
            'Name is required',
            'Name is required',
            [],
            null,
            'items[0].name',
            ''
        );

        $violations = new ConstraintViolationList([$violation1, $violation2]);

        $result = $this->formatter->format($violations);

        $expected = [
            'errors' => [
                'items' => [
                    '0' => [
                        '_root' => ['Item is invalid'],
                        'name' => ['Name is required'],
                    ],
                ],
            ],
        ];

        $this->assertSame($expected, $result);
    }

}
