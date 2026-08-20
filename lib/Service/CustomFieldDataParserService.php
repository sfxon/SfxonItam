<?php declare(strict_types=1);

namespace OCA\SfxonItam\Service;

class CustomFieldDataParserService {
    public function __construct()
    {
    }

    public function getBoolean(mixed $value, bool $required) : ?bool
    {
        if($required === false && ($value === null || $value === "")) {
            return null;
        }

        return (bool)$value;
    }

    public function getDecimal(mixed $value, bool $required) : ?float
    {
        if($required === false && ($value === null || $value === "")) {
            return null;
        }

        $value = str_replace(',', '.', $value);

        return (float)$value;
    }

    public function getInteger(mixed $value, bool $required) : ?float
    {
        if($required === false && ($value === null || $value === "")) {
            return null;
        }

        return (int)$value;
    }

    public function getText(mixed $value, bool $required) : ?string
    {
        if($required === false && ($value === null || $value === "")) {
            return null;
        }

        return (string)$value;
    }
}