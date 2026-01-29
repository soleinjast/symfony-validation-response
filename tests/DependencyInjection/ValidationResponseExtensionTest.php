<?php

declare(strict_types=1);

namespace Soleinjast\ValidationResponse\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Soleinjast\ValidationResponse\Command\TestValidationCommand;
use Soleinjast\ValidationResponse\DependencyInjection\ValidationResponseExtension;
use Soleinjast\ValidationResponse\EventListener\ValidationExceptionListener;
use Soleinjast\ValidationResponse\Formatter\SimpleFormatter;
use Soleinjast\ValidationResponse\Formatter\RFC7807Formatter;
use Soleinjast\ValidationResponse\Formatter\NestedFormatter;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class ValidationResponseExtensionTest extends TestCase
{
    private ValidationResponseExtension $extension;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->extension = new ValidationResponseExtension();
        $this->container = new ContainerBuilder();
    }

    public function testLoadWithDefaultConfiguration(): void
    {
        // Empty config = uses defaults
        $configs = [];

        $this->extension->load($configs, $this->container);

        // SimpleFormatter should be registered
        $this->assertTrue($this->container->has(SimpleFormatter::class));

        // RFC7807Formatter should be registered
        $this->assertTrue($this->container->has(RFC7807Formatter::class));

        // NestedFormatter should be registered
        $this->assertTrue($this->container->has(NestedFormatter::class));

        // Listener should be configured
        $this->assertTrue($this->container->has(ValidationExceptionListener::class));

        $listenerDef = $this->container->getDefinition(ValidationExceptionListener::class);

        // Should use SimpleFormatter by default
        $formatterArg = $listenerDef->getArgument('$formatter');
        $this->assertInstanceOf(Reference::class, $formatterArg);
        $this->assertSame(SimpleFormatter::class, (string) $formatterArg);

        // Should use default status code 422
        $this->assertSame(422, $listenerDef->getArgument('$statusCode'));
    }

    public function testLoadWithSimpleFormat(): void
    {
        $configs = [
            [
                'format' => 'simple',
                'status_code' => 400,
            ],
        ];

        $this->extension->load($configs, $this->container);

        $listenerDef = $this->container->getDefinition(ValidationExceptionListener::class);

        // Should use SimpleFormatter
        $formatterArg = $listenerDef->getArgument('$formatter');
        $this->assertSame(SimpleFormatter::class, (string) $formatterArg);

        // Should use custom status code
        $this->assertSame(400, $listenerDef->getArgument('$statusCode'));
    }

    public function testLoadWithRFC7807Format(): void
    {
        $configs = [
            [
                'format' => 'rfc7807',
                'status_code' => 422,
            ],
        ];

        $this->extension->load($configs, $this->container);

        $listenerDef = $this->container->getDefinition(ValidationExceptionListener::class);

        // Should use RFC7807Formatter
        $formatterArg = $listenerDef->getArgument('$formatter');
        $this->assertSame(RFC7807Formatter::class, (string) $formatterArg);
    }

    public function testLoadWithNestedFormat(): void
    {
        $configs = [
            [
                'format' => 'nested',
                'status_code' => 422,
            ],
        ];

        $this->extension->load($configs, $this->container);

        $listenerDef = $this->container->getDefinition(ValidationExceptionListener::class);

        $formatterArg = $listenerDef->getArgument('$formatter');
        $this->assertSame(NestedFormatter::class, (string) $formatterArg);
    }

    public function testLoadWithCustomRFC7807Configuration(): void
    {
        $configs = [
            [
                'format' => 'rfc7807',
                'rfc7807' => [
                    'type' => 'https://example.com/validation-error',
                    'title' => 'Custom Validation Error',
                ],
            ],
        ];

        $this->extension->load($configs, $this->container);

        // RFC7807Formatter should be configured with custom values
        $formatterDef = $this->container->getDefinition(RFC7807Formatter::class);

        $this->assertSame('https://example.com/validation-error', $formatterDef->getArgument('$type'));
        $this->assertSame('Custom Validation Error', $formatterDef->getArgument('$title'));
    }

    public function testLoadWithDefaultRFC7807Configuration(): void
    {
        $configs = [
            [
                'format' => 'rfc7807',
                // No rfc7807 config = uses defaults
            ],
        ];

        $this->extension->load($configs, $this->container);

        $formatterDef = $this->container->getDefinition(RFC7807Formatter::class);

        // Should use default values
        $this->assertSame('about:blank', $formatterDef->getArgument('$type'));
        $this->assertSame('Validation Failed', $formatterDef->getArgument('$title'));
    }

    public function testFormattersAreNotPublic(): void
    {
        $configs = [];

        $this->extension->load($configs, $this->container);

        // Formatters should not be public (internal use only)
        $simpleFormatterDef = $this->container->getDefinition(SimpleFormatter::class);
        $this->assertFalse($simpleFormatterDef->isPublic());

        $rfc7807FormatterDef = $this->container->getDefinition(RFC7807Formatter::class);
        $this->assertFalse($rfc7807FormatterDef->isPublic());

        $nestedFormatterDef = $this->container->getDefinition(NestedFormatter::class);
        $this->assertFalse($nestedFormatterDef->isPublic());
    }

    public function testMultipleLoadCalls(): void
    {
        // First load with simple format
        $configs1 = [
            ['format' => 'simple'],
        ];
        $this->extension->load($configs1, $this->container);

        // Second load with rfc7807 format (simulates multiple config files)
        $configs2 = [
            ['format' => 'rfc7807'],
        ];
        $this->extension->load($configs2, $this->container);

        $listenerDef = $this->container->getDefinition(ValidationExceptionListener::class);
        $formatterArg = $listenerDef->getArgument('$formatter');

        // Last config wins
        $this->assertSame(RFC7807Formatter::class, (string) $formatterArg);
    }

    public function testStatusCodeRange(): void
    {
        $configs = [
            [
                'status_code' => 400,
            ],
        ];

        $this->extension->load($configs, $this->container);

        $listenerDef = $this->container->getDefinition(ValidationExceptionListener::class);
        $this->assertSame(400, $listenerDef->getArgument('$statusCode'));
    }

    public function testCompleteConfiguration(): void
    {
        $configs = [
            [
                'format' => 'rfc7807',
                'status_code' => 400,
                'rfc7807' => [
                    'type' => 'https://api.example.com/errors/validation',
                    'title' => 'Request Validation Failed',
                ],
            ],
        ];

        $this->extension->load($configs, $this->container);

        // Check listener configuration
        $listenerDef = $this->container->getDefinition(ValidationExceptionListener::class);
        $this->assertSame(400, $listenerDef->getArgument('$statusCode'));

        $formatterArg = $listenerDef->getArgument('$formatter');
        $this->assertSame(RFC7807Formatter::class, (string) $formatterArg);

        // Check RFC7807Formatter configuration
        $formatterDef = $this->container->getDefinition(RFC7807Formatter::class);
        $this->assertSame('https://api.example.com/errors/validation', $formatterDef->getArgument('$type'));
        $this->assertSame('Request Validation Failed', $formatterDef->getArgument('$title'));
    }

    public function testCommandUsesSimpleFormatterByDefault(): void
    {
        $configs = [];

        $this->extension->load($configs, $this->container);

        $this->assertTrue($this->container->has(TestValidationCommand::class));

        $commandDef = $this->container->getDefinition(TestValidationCommand::class);
        $formatterArg = $commandDef->getArgument('$formatter');

        $this->assertInstanceOf(Reference::class, $formatterArg);
        $this->assertSame(SimpleFormatter::class, (string) $formatterArg);
    }

    public function testCommandUsesSimpleFormatterWhenConfigured(): void
    {
        $configs = [
            [
                'format' => 'simple',
            ],
        ];

        $this->extension->load($configs, $this->container);

        $commandDef = $this->container->getDefinition(TestValidationCommand::class);
        $formatterArg = $commandDef->getArgument('$formatter');

        $this->assertSame(SimpleFormatter::class, (string) $formatterArg);
    }

    public function testCommandAndListenerUseSameFormatter(): void
    {
        $configs = [
            [
                'format' => 'rfc7807',
                'rfc7807' => [
                    'type' => 'https://example.com/validation',
                    'title' => 'Validation Error',
                ],
            ],
        ];

        $this->extension->load($configs, $this->container);

        $listenerDef = $this->container->getDefinition(ValidationExceptionListener::class);
        $commandDef = $this->container->getDefinition(TestValidationCommand::class);

        $listenerFormatter = $listenerDef->getArgument('$formatter');
        $commandFormatter = $commandDef->getArgument('$formatter');

        // Both should use the same formatter
        $this->assertSame((string) $listenerFormatter, (string) $commandFormatter);
        $this->assertSame(RFC7807Formatter::class, (string) $commandFormatter);
    }

    public function testCommandFormatterChangesWithConfigurationFormat(): void
    {
        // Load with simple format
        $configs1 = [
            ['format' => 'simple'],
        ];
        $this->extension->load($configs1, $this->container);

        $commandDef = $this->container->getDefinition(TestValidationCommand::class);
        $formatterArg = $commandDef->getArgument('$formatter');
        $this->assertSame(SimpleFormatter::class, (string) $formatterArg);

        // Reload with rfc7807 format
        $configs2 = [
            ['format' => 'rfc7807'],
        ];
        $this->extension->load($configs2, $this->container);

        $commandDef = $this->container->getDefinition(TestValidationCommand::class);
        $formatterArg = $commandDef->getArgument('$formatter');
        $this->assertSame(RFC7807Formatter::class, (string) $formatterArg);
    }

    public function testCommandUsesNestedFormatterWhenConfigured(): void
    {
        $configs = [
            [
                'format' => 'nested',
            ],
        ];

        $this->extension->load($configs, $this->container);

        $commandDef = $this->container->getDefinition(TestValidationCommand::class);
        $formatterArg = $commandDef->getArgument('$formatter');

        $this->assertSame(NestedFormatter::class, (string) $formatterArg);
    }

    public function testCommandWithCompleteRFC7807Configuration(): void
    {
        $configs = [
            [
                'format' => 'rfc7807',
                'status_code' => 400,
                'rfc7807' => [
                    'type' => 'https://api.example.com/errors/validation',
                    'title' => 'Request Validation Failed',
                ],
            ],
        ];

        $this->extension->load($configs, $this->container);

        // Verify command uses RFC7807Formatter
        $commandDef = $this->container->getDefinition(TestValidationCommand::class);
        $formatterArg = $commandDef->getArgument('$formatter');
        $this->assertSame(RFC7807Formatter::class, (string) $formatterArg);

        // Verify RFC7807Formatter is configured correctly
        $formatterDef = $this->container->getDefinition(RFC7807Formatter::class);
        $this->assertSame('https://api.example.com/errors/validation', $formatterDef->getArgument('$type'));
        $this->assertSame('Request Validation Failed', $formatterDef->getArgument('$title'));
    }
}
