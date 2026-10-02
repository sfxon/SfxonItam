<?php declare(strict_types=1);

namespace OCA\SfxonItam\Definition;

final class EntityRegistry
{
    private const MAP = [
        DeviceDefinition::KEY => DeviceDefinition::class,
        DeviceStatusDefinition::KEY => DeviceStatusDefinition::class,
        DeviceTypeDefinition::KEY => DeviceTypeDefinition::class,
        ItamUserDefinition::KEY => ItamUserDefinition::class,
        LocationDefinition::KEY => LocationDefinition::class,
        ManufacturerDefinition::KEY => ManufacturerDefinition::class,
        MerchantDefinition::KEY => MerchantDefinition::class,
        PositionDefinition::KEY => PositionDefinition::class,
        QuantityUnitDefinition::KEY => QuantityUnitDefinition::class,
    ];

    /** @var array<string, EntityDefinition> */
    private array $instances = [];

    public function has(string $key): bool
    {
        return isset(self::MAP[$key]);
    }

    public function get(string $key): EntityDefinition
    {
        if (!isset(self::MAP[$key])) {
            throw new \InvalidArgumentException('Unknown entity definition: ' . $key);
        }

        return $this->instances[$key] ??= new (self::MAP[$key])();
    }

    /**
     * @return array<string, EntityDefinition>
     */
    public function all(): array
    {
        $result = [];
        foreach (array_keys(self::MAP) as $key) {
            $result[$key] = $this->get($key);
        }

        return $result;
    }

    public function fieldDefinitions(): array
    {
        $result = [];
        foreach ($this->all() as $key => $definition) {
            $entityClass = $definition->entityClass;
            if (method_exists($entityClass, 'getFieldDefinition')) {
                $result[$key] = $entityClass::getFieldDefinition();
            }
        }

        return $result;
    }
}