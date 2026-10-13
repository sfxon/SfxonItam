<?php declare(strict_types=1);

namespace OCA\SfxonItam\Service;

class CustomFieldDataParserService {
    public function __construct()
    {
    }

    public function getBoolean(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (bool)$value;
    }

    public function getDecimal(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (string)$value;
        }

        if (is_string($value)) {
            return str_replace(',', '.', trim($value));
        }

        return $value;
    }

    public function getInteger(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', trim($value)) === 1) {
            return (int)trim($value);
        }

        return $value;
    }

    public function getText(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_scalar($value) ? (string)$value : $value;
    }
}