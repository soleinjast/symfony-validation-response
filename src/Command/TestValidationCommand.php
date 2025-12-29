<?php

declare(strict_types=1);

namespace Soleinjast\ValidationResponse\Command;

use Soleinjast\ValidationResponse\Formatter\FormatterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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
        $className = $input->getArgument('class');
        $jsonData = $input->getArgument('data');

        // Resolve class name
        $fullClassName = $this->resolveClassName($className);

        if (!class_exists($fullClassName)) {
            $output->writeln('<error>✗ Class "' . $fullClassName . '" does not exist</error>');
            $output->writeln('');
            $output->writeln('<comment>Tried:</comment>');
            $output->writeln('  - ' . $fullClassName);
            return Command::FAILURE;
        }

        // Deserialize JSON to DTO
        try {
            $dto = $this->serializer->deserialize($jsonData, $fullClassName, 'json');
        } catch (\Throwable $e) {
            $output->writeln('<error>✗ Failed to deserialize JSON</error>');
            $output->writeln('<comment>Error:</comment> ' . $e->getMessage());
            return Command::FAILURE;
        }

        // Validate the DTO
        $violations = $this->validator->validate($dto);

        // Check if validation passed
        if (count($violations) === 0) {
            $output->writeln('<info>✓ Validation passed! No errors found.</info>');
            return Command::SUCCESS;
        }

        // Display errors
        $count = count($violations);
        $output->writeln('<error>✗ Validation Failed (' . $count . ' error' . ($count > 1 ? 's' : '') . ')</error>');
        $output->writeln('');

        foreach ($violations as $violation) {
            $output->writeln(sprintf(
                '  <fg=red>✗</> <fg=yellow>%s</>: %s',
                $violation->getPropertyPath() ?: '(root)',
                $violation->getMessage()
            ));
        }

        // Show formatted output
        $formatted = $this->formatter->format($violations);
        $output->writeln('');
        $output->writeln('<comment>Formatted Output:</comment>');
        $output->writeln(json_encode($formatted, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

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