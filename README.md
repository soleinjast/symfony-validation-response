# Symfony Validation Response Bundle

<p align="center">
  <img src="/docs/doc-banner-img.png" alt="Symfony Validation Response Bundle" width="800">
</p>

<p align="center">
  <a href="https://packagist.org/packages/soleinjast/symfony-validation-response"><img src="https://poser.pugx.org/soleinjast/symfony-validation-response/v" alt="Latest Stable Version"></a>
  <a href="https://packagist.org/packages/soleinjast/symfony-validation-response"><img src="https://poser.pugx.org/soleinjast/symfony-validation-response/downloads" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/soleinjast/symfony-validation-response"><img src="https://poser.pugx.org/soleinjast/symfony-validation-response/license" alt="License"></a>
  <img src="https://img.shields.io/badge/php-%3E%3D8.4-8892BF.svg" alt="PHP Version">
  <img src="https://img.shields.io/badge/symfony-%5E6.3%20%7C%20%5E7.0%20%7C%20%5E8.0-000000.svg" alt="Symfony Version">
</p>
A lightweight Symfony bundle that automatically transforms validation errors from `#[MapRequestPayload]`, `#[MapQueryString]`, and `#[MapUploadedFile]` attributes into clean, developer-friendly JSON responses.

Stop writing repetitive error handling code in every controller. Let this bundle handle it for you.

---

## 📑 Table of Contents

- [Features](#-features)
- [Installation](#-installation)
- [Quick Start](#-quick-start)
- [Response Formats](#-response-formats)
- [Configuration](#%EF%B8%8F-configuration)
- [Custom Formatter](#-custom-formatter)
- [Usage Examples](#-usage-examples)
- [CLI Testing Tool](#-cli-testing-tool)
- [Testing](#-testing)
- [Requirements](#-requirements)
- [Contributing](#-contributing)
- [License](#-license)

---

## ✨ Features

- ✅ **Zero Configuration** - Works immediately after installation with sensible defaults
- ✅ **Multiple Formats** - Simple (default), Nested, or RFC 7807 Problem Details
- ✅ **Clean JSON Responses** - No verbose Symfony debug output
- ✅ **RFC 7807 Compliant** - Industry-standard Problem Details for HTTP APIs
- ✅ **Automatic Error Formatting** - Intercepts validation exceptions and formats them consistently
- ✅ **Type-Safe** - Built with PHP 8.4+ strict types and modern practices
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
    "name": [
      "Product name is required"
    ],
    "price": [
      "Price must be zero or positive"
    ],
    "status": [
      "Status must be one of: active, inactive, draft"
    ]
  }
}
```

**If a field has multiple validation errors, they're grouped together:**

```json
{
  "errors": {
    "name": [
      "Product name is required",
      "Product name must be at least 3 characters long"
    ]
  }
}
```

**No extra code needed!** The bundle handles everything automatically.

---

## 🎨 Response Formats

The bundle supports two output formats:

### Simple Format (Default)

Clean, minimal error responses:

```json
{
  "errors": {
    "email": [
      "Invalid email format"
    ],
    "age": [
      "Must be at least 18"
    ]
  }
}
```

### Nested Format

Nested objects are built from dotted property paths:

```json
{
  "errors": {
    "address": {
      "city": [
        "Invalid city"
      ]
    }
  }
}
```

### RFC 7807 Problem Details Format

Industry-standard error responses ([RFC 7807](https://tools.ietf.org/html/rfc7807)):

```json
{
  "type": "https://example.com/validation-error",
  "title": "Validation Failed",
  "status": 422,
  "detail": "2 validation errors detected",
  "violations": [
    {
      "field": "email",
      "message": "Invalid email format",
      "code": "bd79c0ab-ddba-46cc-a703-a7a4b08de310"
    },
    {
      "field": "age",
      "message": "Must be at least 18",
      "code": "..."
    }
  ]
}
```

**Note:** RFC 7807 responses include the correct `Content-Type: application/problem+json` header.

---

## ⚙️ Configuration

The bundle works out-of-the-box with zero configuration. However, you can customize it if needed.

### Basic Configuration

Create `config/packages/validation_response.yaml`:

```yaml
validation_response:
    # Choose response format: 'simple' or 'rfc7807' (default: 'simple')
    format: 'simple'
    # HTTP status code for validation errors (default: 422)
    status_code: 422
```

### RFC 7807 Configuration

```yaml
validation_response:
    format: 'rfc7807'
    rfc7807:
        # URI reference identifying the problem type
        type: 'https://api.example.com/errors/validation'
        # Short, human-readable summary
        title: 'Validation Error'

```

**Note:** RFC 7807 format always returns HTTP status 422 (Unprocessable Entity) as per the standard. The `status_code` configuration only applies to the Simple format.

### Available Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `format` | `string` | `'simple'` | Response format: `'simple'`, `'nested'`, or `'rfc7807'` |
| `status_code` | `integer` | `422` | HTTP status code for validation errors (400-599) |
| `rfc7807.type` | `string` | `'about:blank'` | URI identifying the problem type |
| `rfc7807.title` | `string` | `'Validation Failed'` | Human-readable problem summary |

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
  "timestamp": "2025-12-27T14:30:00+00:00"
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
            $errors[$violation->getPropertyPath()][] = $violation->getMessage();
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
            $errors[$violation->getPropertyPath()][] = $this->translator->trans(
                $violation->getMessage(),
                $violation->getParameters()
            );
        }

        return ['errors' => $errors];
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
    "name": [
      "This field is required"
    ],
    "address.street": [
      "This field is required"
    ],
    "address.zipCode": [
      "Invalid zip code format"
    ]
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

## 🧪 CLI Testing Tool

Test your DTOs directly from the command line without making HTTP requests:
```bash
# Test with invalid data
php bin/console validation:test CreateProductDto '{"name":"","price":-100}'

# Test with valid data
php bin/console validation:test CreateProductDto '{"name":"Laptop","price":1000}'

# Use fully qualified class name
php bin/console validation:test 'App\Dto\CreateProductDto' '{"name":"Test"}'
```

**Example output for invalid data:**
```
 Validation Test
 ===============

Testing: App\Dto\CreateProductDto
----------------------------------

 ✓ JSON deserialized successfully

 [ERROR] Validation Failed (2 errors)

 ------- ---------------------------------- ----------
  Field   Error Message                      Code
 ------- ---------------------------------- ----------
  name    Product name is required           c1051bb4...
  price   Price must be zero or positive     778b3c5a...
 ------- ---------------------------------- ----------

 Formatted Output
 ----------------

 {
     "errors": {
         "name": [
             "Product name is required"
         ],
         "price": [
             "Price must be zero or positive"
         ]
     }
 }
```

### Format Consistency

The CLI command **respects your format configuration** from `validation_response.yaml`. This ensures that CLI testing output matches your actual API responses exactly.

**Example with RFC 7807 format:**
```yaml
# config/packages/validation_response.yaml
validation_response:
    format: 'rfc7807'
    rfc7807:
        type: 'https://api.example.com/validation-error'
        title: 'Request Validation Failed'
```

When you run the command, the formatted output will use RFC 7807:
```json
{
    "type": "https://api.example.com/validation-error",
    "title": "Request Validation Failed",
    "status": 422,
    "detail": "2 validation errors detected",
    "violations": [
        {
            "field": "name",
            "message": "Product name is required",
            "code": "c1051bb4-d103-4f74-8988-acbcafc7fdc3"
        },
        {
            "field": "price",
            "message": "Price must be zero or positive",
            "code": "778b3c5a-d8f5-4f8a-9e98-c2e07b5d6f3d"
        }
    ]
}
```

### Command Options

The command automatically resolves DTO class names from common namespaces:

- `App\Dto\`
- `App\DTO\`
- `App\Request\`
- `App\Model\`

So you can use either:
- Short name: `CreateProductDto`
- Fully qualified: `App\Dto\CreateProductDto`

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

- **PHP**: 8.4 or higher
- **Symfony**: 6.3 or higher (including Symfony 7.x and 8.0)

### Why These Requirements?

- **PHP 8.4+**: Enables modern features and is required by Symfony 8.0 components
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
- [RFC 7807: Problem Details for HTTP APIs](https://tools.ietf.org/html/rfc7807)

---

**Happy coding!** 🚀
