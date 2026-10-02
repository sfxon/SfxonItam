<?php declare(strict_types=1);

namespace OCA\SfxonItam\Service;

use OCA\SfxonItam\Definition\EntityDefinition;
use Psr\Container\ContainerInterface;

class DeleteGuardService
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function findViolation(EntityDefinition $definition, int $id): ?string
    {
        foreach ($definition->deleteGuards as $guard) {
            $mapper = $this->container->get($guard->mapperClass);

            if ($mapper->isEntityValueInUse($guard->column, $id)) {
                return $guard->message;
            }
        }

        return null;
    }
}