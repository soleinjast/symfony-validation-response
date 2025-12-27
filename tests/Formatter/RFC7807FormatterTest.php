<?php

declare(strict_types=1);

namespace Soleinjast\ValidationResponse\Tests\Formatter;

use PHPUnit\Framework\TestCase;
use Soleinjast\ValidationResponse\Formatter\RFC7807Formatter;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

final class RFC7807FormatterTest extends TestCase
{
    private readonly RFC7807Formatter $formatter;
    protected function setUp(): void
    {
        $this->formatter = new RFC7807Formatter();
    }

    public function testFormatEmptyViolations(): void
    {
        $violations = new ConstraintViolationList();
        $result = $this->formatter->format($violations);
        $this->assertEmpty($result['violations']);
        $this->assertSame('about:blank', $result['type']);
        $this->assertSame('Validation Failed', $result['title']);
        $this->assertSame('0 validation errors detected', $result['detail']);
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

        $expected = [
            [
                'field' => 'name',
                'message' => 'This field is required',
                'code' => null
            ]
        ];

        $violations =  new ConstraintViolationList([$violation]);
        $result = $this->formatter->format($violations);
        $this->assertSame('1 validation error detected', $result['detail']);
        $this->assertCount(1, $result['violations']);
        $this->assertEquals($expected, $result['violations']);
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
            [
                'field' => 'name',
                'message' => 'Name is required',
                'code' => null
            ],
            [
                'field' => 'price',
                'message' => 'Price must be positive',
                'code' => null
            ]
        ];

        $this->assertEquals($expected, $result['violations']);
    }

    public function testFormatNestedPropertyPath(): void{
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
            [
                'field' => 'address.city',
                'message' => 'Invalid city',
                'code' => null
            ]
        ];
        $this->assertEquals($expected, $result['violations']);
    }
}