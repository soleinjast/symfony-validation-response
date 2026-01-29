<?php

declare(strict_types=1);

namespace Soleinjast\ValidationResponse\Command;

use Throwable;
use Soleinjast\ValidationResponse\Formatter\FormatterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class TestValidationCommand extends Command
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer,
        private readonly FormatterInterface $formatter
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('validation:test')
            ->setDescription('Test validation on a DTO with sample data')
            ->addArgument('class', InputArgument::REQUIRED, 'DTO class name (e.g., CreateProductDto)')
            ->addArgument('data', InputArgument::REQUIRED, 'JSON data to validate')
            ->setHelp(<<<'HELP'
                Test validation on a DTO without making HTTP requests.
                
                <info>Examples:</info>
                
                  # Test with invalid data
                  <comment>php bin/console validation:test CreateProductDto '{"name":"","price":-100}'</comment>
                
                  # Test with valid data
                  <comment>php bin/console validation:test CreateProductDto '{"name":"Laptop","price":1000}'</comment>
                
                  # Use fully qualified class name
                  <comment>php bin/console validation:test 'App\Dto\CreateProductDto' '{"name":"Test"}'</comment>
                HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $className = $input->getArgument('class');
        $jsonData = $input->getArgument('data');

        // Header
        $io->title('Validation Test');

        // Resolve class name
        $fullClassName = $this->resolveClassName($className);

        if (!class_exists($fullClassName)) {
            $io->error('Class not found');
            $io->writeln('  Tried: ' . $fullClassName);
            return Command::FAILURE;
        }

        $io->section('Testing: ' . $fullClassName);

        // Deserialize JSON to DTO
        try {
            $dto = $this->serializer->deserialize($jsonData, $fullClassName, 'json');
            $io->text('✓ JSON deserialized successfully');
        } catch (Throwable $e) {
            $io->error('Failed to deserialize JSON');
            $io->block($e->getMessage(), null, 'fg=white;bg=red', ' ', true);
            return Command::FAILURE;
        }

        // Validate the DTO
        $violations = $this->validator->validate($dto);

        // Check if validation passed
        if (count($violations) === 0) {
            $io->success('Validation passed! No errors found.');
            return Command::SUCCESS;
        }

        // Display errors
        $count = count($violations);
        $io->error('Validation Failed (' . $count . ' error' . ($count > 1 ? 's' : '') . ')');
        $io->newLine();

        // Create table for errors
        $rows = [];
        foreach ($violations as $violation) {
            $rows[] = [
                $violation->getPropertyPath() ?: '(root)',
                $violation->getMessage(),
                substr($violation->getCode() ?? '', 0, 8) . '...',
            ];
        }

        $io->table(
            ['Field', 'Error Message', 'Code'],
            $rows
        );

        // Show formatted output
        $formatted = $this->formatter->format($violations);
        $io->section('Formatted Output');
        $io->block(
            json_encode($formatted, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            null,
            'fg=white;bg=blue',
            ' ',
            true
        );

        return Command::FAILURE;
    }

    private function resolveClassName(string $className): string
    {
        if (str_contains($className, '\\')) {
            return $className;
        }

        $namespaces = [
            'App\\Dto\\',
            'App\\DTO\\',
            'App\\Request\\',
            'App\\Model\\',
        ];

        foreach ($namespaces as $namespace) {
            $fullName = $namespace . $className;
            if (class_exists($fullName)) {
                return $fullName;
            }
        }

        return $className;
    }
}