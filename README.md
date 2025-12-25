# Symfony Validation Response Bundle

[![Latest Stable Version](https://poser.pugx.org/soleinjast/symfony-validation-response/v)](https://packagist.org/packages/soleinjast/symfony-validation-response)
[![Total Downloads](https://poser.pugx.org/soleinjast/symfony-validation-response/downloads)](https://packagist.org/packages/soleinjast/symfony-validation-response)
[![License](https://poser.pugx.org/soleinjast/symfony-validation-response/license)](https://packagist.org/packages/soleinjast/symfony-validation-response)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-8892BF.svg)](https://php.net/)
[![Symfony Version](https://img.shields.io/badge/symfony-%5E6.3%20%7C%20%5E7.0-000000.svg)](https://symfony.com/)

A lightweight Symfony bundle that automatically transforms validation errors from `#[MapRequestPayload]`, `#[MapQueryString]`, and `#[MapUploadedFile]` attributes into clean, developer-friendly JSON responses.

Stop writing repetitive error handling code in every controller. Let this bundle handle it for you.

---

## 📑 Table of Contents

- [Features](#-features)
- [Installation](#-installation)
- [Quick Start](#-quick-start)
- [Configuration](#%EF%B8%8F-configuration)
- [Custom Formatter](#-custom-formatter)
- [Usage Examples](#-usage-examples)
- [Testing](#-testing)
- [Requirements](#-requirements)
- [Contributing](#-contributing)
- [License](#-license)

---

## ✨ Features

- ✅ **Zero Configuration** - Works immediately after installation with sensible defaults
- ✅ **Clean JSON Responses** - Removes verbose Symfony debug output in favor of clean error messages
- ✅ **Automatic Error Formatting** - Intercepts validation exceptions and formats them consistently
- ✅ **Type-Safe** - Built with PHP 8.1+ strict types and modern practices
- ✅ **Lightweight** - Minimal footprint with no external dependencies beyond Symfony core
- ✅ **Well Tested** - Comprehensive PHPUnit test coverage
- ✅ **Production Ready** - Battle-tested error handling for REST APIs

---

## 📦 Installation

Install via Composer:

```bash
composer require soleinjast/symfony-validation-response
```

The bundle will auto-register itself if you're using Symfony Flex. Otherwise, add it manually to `config/bundles.php`:

```php
return [
    // ...
    Soleinjast\ValidationResponse\ValidationResponseBundle::class => ['all' => true],
];
```

That's it! No additional configuration required.

---

## 🚀 Quick Start

### Step 1: Create a DTO (Data Transfer Object)

Define your request structure with validation constraints:

```php
<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateProductDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Product name is required')]
        #[Assert\Length(min: 3, minMessage: 'Name must be at least 3 characters')]
        public string $name,

        #[Assert\NotBlank(message: 'Description is required')]
        public string $description,

        #[Assert\PositiveOrZero(message: 'Price must be zero or positive')]
        public int $price,

        #[Assert\Choice(
            choices: ['active', 'inactive', 'draft'],
            message: 'Status must be one of: {{ choices }}'
        )]
        public string $status = 'draft',
    ) {}
}
```

### Step 2: Use in Your Controller

Apply the `#[MapRequestPayload]` attribute to your controller parameter:

```php
<?php

namespace App\Controller;

use App\Dto\CreateProductDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class ProductController extends AbstractController
{
    #[Route('/api/products', methods: ['POST'], format: 'json')]
    public function create(
        #[MapRequestPayload] CreateProductDto $dto
    ): JsonResponse {
        // If code reaches here, validation passed!
        // Your business logic goes here...
        
        return $this->json([
            'message' => 'Product created successfully',
            'data' => [
                'name' => $dto->name,
                'price' => $dto->price,
            ],
        ], 201);
    }
}
```

### Step 3: Enjoy Clean Error Responses

**When you send an invalid request:**

```bash
curl -X POST http://localhost:8000/api/products \
  -H "Content-Type: application/json" \
  -d '{
    "name": "",
    "description": "Test product",
    "price": -100,
    "status": "invalid"
  }'
```

**You automatically get a clean JSON response (422 Unprocessable Entity):**

```json
{
  "errors": {
    "name": "Product name is required",
    "price": "Price must be zero or positive",
    "status": "Status must be one of: active, inactive, draft"
  }
}
```

**No extra code needed!** The bundle handles everything automatically.

---

## ⚙️ Configuration

The bundle works out-of-the-box with zero configuration. However, you can customize it if needed.

### Optional Configuration

Create `config/packages/validation_response.yaml`:

```yaml
validation_response:
  # HTTP status code for validation errors (default: 422)
  status_code: 422
```

### Available Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `status_code` | `integer` | `422` | HTTP status code returned for validation errors |

---

## 🎨 Custom Formatter

Want complete control over your error response format? Create your own custom formatter!

### Step 1: Create Your Formatter Class

Create a class that implements `FormatterInterface`:

```php
<?php

namespace App\Formatter;

use Soleinjast\ValidationResponse\Formatter\FormatterInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

final class MyCustomFormatter implements FormatterInterface
{
    public function format(ConstraintViolationListInterface $violations): array
    {
        $errors = [];
        
        foreach ($violations as $violation) {
            $errors[] = [
                'field' => $violation->getPropertyPath(),
                'error' => $violation->getMessage(),
                'code' => $violation->getCode(),
            ];
        }

        return [
            'success' => false,
            'validation_errors' => $errors,
            'error_count' => count($errors),
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }
}
```

### Step 2: Register Your Formatter

Update `config/services.yaml`:

```yaml
services:
  # Your custom formatter
  App\Formatter\MyCustomFormatter: ~

  # Override the default formatter
  Soleinjast\ValidationResponse\EventListener\ValidationExceptionListener:
    arguments:
      $formatter: '@App\Formatter\MyCustomFormatter'
      $statusCode: 422
    tags:
      - { name: kernel.event_subscriber }
```

**Important:** The `tags` section ensures the listener is properly registered as an event subscriber.

### Step 3: Get Custom Response Format

Now your validation errors will use your custom format:

```json
{
  "success": false,
  "validation_errors": [
    {
      "field": "name",
      "error": "Product name is required",
      "code": "c1051bb4-d103-4f74-8988-acbcafc7fdc3"
    },
    {
      "field": "price",
      "error": "Price must be positive",
      "code": "e09e52d0-b549-4ba1-8b4e-420aad76f0de"
    }
  ],
  "error_count": 2,
  "timestamp": "2025-12-25T14:30:00+00:00"
}
```

### Custom Formatter Examples

**Example 1: Add Request ID to Errors**

```php
final class TrackedFormatter implements FormatterInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {}

    public function format(ConstraintViolationListInterface $violations): array
    {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }

        return [
            'errors' => $errors,
            'request_id' => $this->requestStack->getCurrentRequest()?->headers->get('X-Request-ID'),
            'timestamp' => time(),
        ];
    }
}
```

**Example 2: Localized Error Messages**

```php
final class LocalizedFormatter implements FormatterInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {}

    public function format(ConstraintViolationListInterface $violations): array
    {
        $errors = [];
        
        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = $this->translator->trans(
                $violation->getMessage(),
                $violation->getParameters()
            );
        }

        return ['errors' => $errors];
    }
}
```

**Example 3: Nested Error Structure**

```php
final class NestedFormatter implements FormatterInterface
{
    public function format(ConstraintViolationListInterface $violations): array
    {
        $errors = [];
        
        foreach ($violations as $violation) {
            $path = $violation->getPropertyPath();
            $parts = explode('.', $path);
            
            $current = &$errors;
            foreach ($parts as $part) {
                if (!isset($current[$part])) {
                    $current[$part] = [];
                }
                $current = &$current[$part];
            }
            $current = $violation->getMessage();
        }

        return ['errors' => $errors];
    }
}
```

Response:
```json
{
  "errors": {
    "name": "This field is required",
    "address": {
      "street": "This field is required",
      "city": "This field is required"
    }
  }
}
```

---

## 💡 Usage Examples

### Example 1: User Registration

```php
// src/Dto/RegisterUserDto.php
final class RegisterUserDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email(message: 'Please provide a valid email')]
        public string $email,

        #[Assert\NotBlank]
        #[Assert\Length(min: 8, minMessage: 'Password must be at least 8 characters')]
        #[Assert\Regex(
            pattern: '/[A-Z]/',
            message: 'Password must contain at least one uppercase letter'
        )]
        public string $password,

        #[Assert\NotBlank]
        #[Assert\Length(min: 2)]
        public string $username,
    ) {}
}

// src/Controller/AuthController.php
#[Route('/api/register', methods: ['POST'], format: 'json')]
public function register(
    #[MapRequestPayload] RegisterUserDto $dto
): JsonResponse {
    // Validation passed - create user
    return $this->json(['message' => 'User registered'], 201);
}
```

### Example 2: Nested DTOs

```php
final class AddressDto
{
    public function __construct(
        #[Assert\NotBlank]
        public string $street,

        #[Assert\NotBlank]
        public string $city,

        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^\d{5}$/')]
        public string $zipCode,
    ) {}
}

final class CreateCustomerDto
{
    public function __construct(
        #[Assert\NotBlank]
        public string $name,

        #[Assert\Valid]
        public AddressDto $address,
    ) {}
}
```

**Error response for nested validation:**

```json
{
  "errors": {
    "name": "This field is required",
    "address.street": "This field is required",
    "address.zipCode": "Invalid zip code format"
  }
}
```

### Example 3: Query String Validation

```php
final class SearchProductsDto
{
    public function __construct(
        #[Assert\Length(min: 3)]
        public ?string $query = null,

        #[Assert\Choice(choices: ['name', 'price', 'created_at'])]
        public string $sortBy = 'created_at',

        #[Assert\Choice(choices: ['asc', 'desc'])]
        public string $order = 'desc',
    ) {}
}

#[Route('/api/products/search', methods: ['GET'], format: 'json')]
public function search(
    #[MapQueryString] SearchProductsDto $dto
): JsonResponse {
    // Query parameters validated automatically
    return $this->json(['results' => []]);
}
```

### Example 4: File Upload Validation

```php
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/api/upload', methods: ['POST'], format: 'json')]
public function upload(
    #[MapUploadedFile([
        new Assert\File(maxSize: '5M'),
        new Assert\Image(mimeTypes: ['image/jpeg', 'image/png']),
    ])]
    UploadedFile $file
): JsonResponse {
    // File validated automatically
    return $this->json(['message' => 'File uploaded']);
}
```

---

## 🧪 Testing

Run the test suite:

```bash
# Install dependencies
composer install

# Run tests
vendor/bin/phpunit

# Run tests with coverage
vendor/bin/phpunit --coverage-html coverage
```

---

## 📋 Requirements

- **PHP**: 8.1 or higher
- **Symfony**: 6.3 or higher (including Symfony 7.x)

### Why These Requirements?

- **PHP 8.1+**: Enables modern features like `readonly` properties and constructor property promotion
- **Symfony 6.3+**: Required for `#[MapRequestPayload]` attribute support

---

## 🤝 Contributing

Contributions are welcome and appreciated! Here's how you can help:

### Reporting Bugs

If you find a bug, please open an issue with:
- Clear description of the problem
- Steps to reproduce
- Expected vs actual behavior
- Your PHP and Symfony versions

### Suggesting Features

Feature requests are welcome! Please:
- Check existing issues first
- Explain the use case clearly
- Consider backward compatibility

### Pull Requests

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Write tests for your changes
4. Ensure all tests pass (`vendor/bin/phpunit`)
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to your fork (`git push origin feature/amazing-feature`)
7. Open a Pull Request

### Development Setup

```bash
git clone https://github.com/soleinjast/symfony-validation-response.git
cd symfony-validation-response
composer install
vendor/bin/phpunit
```

---

## 🐛 Support & Issues

- **GitHub Issues**: [Report a bug or request a feature](https://github.com/soleinjast/symfony-validation-response/issues)
- **Discussions**: [Ask questions or share ideas](https://github.com/soleinjast/symfony-validation-response/discussions)

---

## 📝 License

This package is open-source software licensed under the [MIT License](LICENSE).

---

## 👏 Credits

Created and maintained by **[Soleinjast](https://github.com/soleinjast)**.

Inspired by the need for cleaner API error responses in Symfony applications.

---

## 🌟 Show Your Support

If this package helps you, please consider:

- ⭐ **Starring** the repository
- 🐦 **Sharing** it on social media
- 💬 **Writing** about your experience

---

## 📚 Related Resources

- [Symfony Validation Documentation](https://symfony.com/doc/current/validation.html)
- [MapRequestPayload Documentation](https://symfony.com/doc/current/controller.html#mapping-request-payload)
- [REST API Best Practices](https://www.ietf.org/rfc/rfc7807.txt)

---

**Happy coding!** 🚀