<?php

declare(strict_types=1);

namespace Soleinjast\ValidationResponse\Formatter;

/**
 * Formatter that builds nested error objects from dotted property paths.
 */
final class NestedFormatter implements FormatterInterface
{
    public function format(\Symfony\Component\Validator\ConstraintViolationListInterface $violations): array
    {
        $errors = [];

        foreach ($violations as $violation) {
            $path = $violation->getPropertyPath();
            $message = $violation->getMessage();

            if ($path === '') {
                $errors['_root'][] = $message;
                continue;
            }

            $segments = $this->splitPropertyPath($path);
            $node = &$errors;

            foreach ($segments as $index => $segment) {
                $isLast = $index === count($segments) - 1;

                if ($isLast) {
                    $this->appendMessage($node, $segment, $message);
                    continue;
                }

                $node[$segment] = $this->ensureObjectNode($node[$segment] ?? null);

                $node = &$node[$segment];
            }

            unset($node);
        }

        return ['errors' => $errors];
    }

    /**
     * Split Symfony property paths into segments.
     * Supports dotted paths and bracketed indices/keys (e.g. items[0].name).
     */
    private function splitPropertyPath(string $path): array
    {
        preg_match_all('/[^.\[\]]+|\[(.*?)\]/', $path, $matches);

        $segments = [];
        foreach ($matches[0] as $index => $match) {
            if ($match[0] === '[') {
                $segment = $matches[1][$index];
                $segments[] = trim($segment, "\"'");
                continue;
            }

            $segments[] = $match;
        }

        return $segments;
    }

    private function ensureObjectNode(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        if ($this->isMessageList($value)) {
            return ['_root' => $value];
        }

        return $value;
    }

    private function appendMessage(array &$node, string $segment, string $message): void
    {
        if (!array_key_exists($segment, $node)) {
            $node[$segment] = [$message];
            return;
        }

        if ($this->isMessageList($node[$segment])) {
            $node[$segment][] = $message;
            return;
        }

        if (!is_array($node[$segment])) {
            $node[$segment] = [$message];
            return;
        }

        $node[$segment]['_root'][] = $message;
    }

    private function isMessageList(mixed $value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        if (!array_is_list($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (!is_string($item)) {
                return false;
            }
        }

        return true;
    }
}
