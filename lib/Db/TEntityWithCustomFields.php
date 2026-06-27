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
}