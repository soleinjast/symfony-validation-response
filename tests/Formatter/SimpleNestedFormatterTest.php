<?php

declare(strict_types=1);

namespace Soleinjast\ValidationResponse\Tests\Formatter;

use PHPUnit\Framework\TestCase;
use Soleinjast\ValidationResponse\Formatter\SimpleNestedFormatter;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

final class SimpleNestedFormatterTest extends TestCase
{
    private readonly SimpleNestedFormatter $formatter;
    protected function setUp(): void
    {
        $this->formatter = new SimpleNestedFormatter();
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

    public function testFormatMultipleViolations(): void {
        $violation1 = new ConstraintViolation(
            'Name is required',
            'Name is required',
            [],
            null,
            'name',
            ''
        );
        $violation2 = new ConstraintViolation(
            'Price must be positive',
            'Price must be positive',
            [],
            null,
            'price',
            -100
        );

        $violations = new ConstraintViolationList([$violation1, $violation2]);
        $result = $this->formatter->format($violations);
        $expected = [
            'errors' => [
                'name' => ['Name is required'],
                'price' => ['Price must be positive'],
            ]
        ];
        $this->assertSame($expected, $result);
    }

    public function testFormatNestedPropertyPath(): void{
        $constraints = [
            new ConstraintViolation(
                'Invalid city',
                'Invalid city',
                [],
                null,
                'address.city',
                ''
            ),
            new ConstraintViolation(
                'Wrong city',
                'Wrong city',
                [],
                null,
                'address.city',
                ''
            ),
        ];
        $violations = new ConstraintViolationList($constraints);
        $result = $this->formatter->format($violations);
        $expected = [
            'errors' => [
                'address' => [
                    'city' => [
                        'Invalid city',
                        'Wrong city',
                    ],
                ],
            ]
        ];
        $this->assertSame($expected, $result);
    }
}
