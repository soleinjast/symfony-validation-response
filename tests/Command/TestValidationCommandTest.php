<?php

declare(strict_types=1);

namespace Soleinjast\ValidationResponse\Tests\Command;

use PHPUnit\Framework\TestCase;
use Soleinjast\ValidationResponse\Command\TestValidationCommand;
use Soleinjast\ValidationResponse\Formatter\SimpleFormatter;
use Soleinjast\ValidationResponse\Tests\Fixtures\TestDto;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validation;

final class TestValidationCommandTest extends TestCase
{
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $serializer = new Serializer(
            [new PropertyNormalizer()],
            [new JsonEncoder()]
        );

        $formatter = new SimpleFormatter();

        $command = new TestValidationCommand($validator, $serializer, $formatter);

        $application = new Application();
        $application->addCommand($command);

        $this->commandTester = new CommandTester($command);
    }

    public function testCommandWithValidData(): void
    {
        $this->commandTester->execute([
            'class' => TestDto::class,
            'data' => '{"name":"Laptop","price":1000}',
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Validation passed', $output);
        $this->assertSame(0, $this->commandTester->getStatusCode());
    }

    public function testCommandWithInvalidData(): void
    {
        $this->commandTester->execute([
            'class' => TestDto::class,
            'data' => '{"name":"","price":-100}',
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Validation Failed', $output);
        $this->assertStringContainsString('Name is required', $output);
        $this->assertStringContainsString('Price must be positive', $output);
        $this->assertStringContainsString('Formatted Output', $output);
        $this->assertSame(1, $this->commandTester->getStatusCode());
    }

    public function testCommandWithNonExistentClass(): void
    {
        $this->commandTester->execute([
            'class' => 'NonExistentClass',
            'data' => '{}',
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('does not exist', $output);
        $this->assertSame(1, $this->commandTester->getStatusCode());
    }

    public function testCommandWithInvalidJson(): void
    {
        $this->commandTester->execute([
            'class' => TestDto::class,
            'data' => 'invalid-json{',
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Failed to deserialize', $output);
        $this->assertSame(1, $this->commandTester->getStatusCode());
    }

    public function testCommandShowsFormattedOutput(): void
    {
        $this->commandTester->execute([
            'class' => TestDto::class,
            'data' => '{"name":"","price":0}',
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('"errors":', $output);
        $this->assertStringContainsString('"name":', $output);
    }

    public function testCommandWithShortClassName(): void
    {
        $this->commandTester->execute([
            'class' => 'TestDto',
            'data' => '{"name":"Test","price":100}',
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('does not exist', $output);
        $this->assertSame(1, $this->commandTester->getStatusCode());
    }

    public function testCommandWithFullyQualifiedClassName(): void
    {
        $this->commandTester->execute([
            'class' => TestDto::class,
            'data' => '{"name":"Laptop","price":1000}',
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Validation passed', $output);
        $this->assertSame(0, $this->commandTester->getStatusCode());
    }
}