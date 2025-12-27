<?php

declare(strict_types=1);

namespace Soleinjast\ValidationResponse\Formatter;
class RFC7807Formatter implements FormatterInterface
{

    /**
     * RFC 7807 Problem Details formatter
     *
     * Implements the IETF standard for HTTP API problem details.
     *
     * @see https://tools.ietf.org/html/rfc7807
     * @see https://www.rfc-editor.org/rfc/rfc7807.html
     */
    public function __construct(
        private readonly string $type = 'about:blank',
        private readonly string $title = 'Validation Failed')
    {}

    public function format(\Symfony\Component\Validator\ConstraintViolationListInterface $violations): array
    {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[] = [
                'field' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
                'code' => $violation->getCode(),
            ];
        }
        $violationCount = count($violations);
        return [
            'type' => $this->type,
            'title' => $this->title,
            'status' => 422,
            'detail' => sprintf(
                '%d validation %s detected',
                $violationCount,
                $violationCount === 1 ? 'error' : 'errors'
            ),
            'violations' => $errors,
        ];
    }
}