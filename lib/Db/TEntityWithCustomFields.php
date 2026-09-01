<?php declare(strict_types=1);

namespace OCA\SfxonItam\Db;

trait TEntityWithCustomFields {
    /** @var array<string, mixed> */
    private array $dynamicAttributes = [];

    protected function setter(string $name, array $args): void {
        if (property_exists($this, $name)) {
            parent::setter($name, $args);
            return;
        }

        $value = $args[0];
        if (!array_key_exists($name, $this->dynamicAttributes)
            || $this->dynamicAttributes[$name] !== $value) {
            $this->markFieldUpdated($name);
        }
        $this->dynamicAttributes[$name] = $value;
    }

    protected function getter(string $name): mixed {
        if (property_exists($this, $name)) {
            return parent::getter($name);
        }

        if (array_key_exists($name, $this->dynamicAttributes)) {
            return $this->dynamicAttributes[$name];
        }

        throw new \BadFunctionCallException($name . ' is not a valid attribute');
    }

    private function snakeToCamel(string $snakeCase): string {
        return lcfirst(str_replace('_', '', ucwords($snakeCase, '_')));
    }

    /**
     * @param mixed $customFieldsDefinition array<int, array{technicalName?: string}>|null
     * @return array<string, mixed>
     */
    protected function jsonSerializeFields(mixed $customFieldsDefinition = null): array {
        $retval = [];

        foreach (static::getFieldDefinition() as $definition) {
            $property = $this->snakeToCamel($definition['name']);
            $getter = 'get' . ucfirst($property);
            $retval[$property] = $this->$getter();
        }

        $retval['customFields'] = $this->extractCustomFields($customFieldsDefinition);

        return $retval;
    }

    /**
     * @param mixed $customFieldsDefinition array<int, array{technicalName?: string}>|null
     * @return array<string, mixed>
     */
    protected function extractCustomFields(mixed $customFieldsDefinition): array {
        $result = [];

        if (!is_array($customFieldsDefinition) || empty($customFieldsDefinition)) {
            return $result;
        }

        foreach ($customFieldsDefinition as $definition) {
            $technicalName = $definition['technicalName'] ?? null;
            if ($technicalName === null) {
                continue;
            }

            $attributeKey = 'custom' . str_replace('_', '', ucwords($technicalName, '_'));

            if (!array_key_exists($attributeKey, $this->dynamicAttributes)) {
                continue;
            }

            $result[$technicalName] = $this->dynamicAttributes[$attributeKey];
        }

        return $result;
    }
}