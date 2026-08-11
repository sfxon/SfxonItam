<?php declare(strict_types=1);

namespace OCA\SfxonItam\Service;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\AppFramework\IAppContainer;

class CustomFieldEntityRegistryService {
    private const REGISTRY = [
        'user' => [
            'mapper' => \OCA\SfxonItam\Db\ItamUserMapper::class,
            'findByIdMethod' => 'findById',
        ],
        'merchant' => [
            'mapper'  => \OCA\SfxonItam\Db\MerchantMapper::class,
            'findByIdMethod' => 'findById',
        ],
        'position' => [
            'mapper' => \OCA\SfxonItam\Db\PositionMapper::class,
            'findByIdMethod' => 'findById',
        ],
    ];

    private array $resolvedMappers = [];

    public function __construct(
        private IAppContainer $container,
    ) {
    }

    public function getDefinition(string $entity): array {
        if (!isset(self::REGISTRY[$entity])) {
            throw new \InvalidArgumentException(
                sprintf('No mapper for entity "%s" registered.', $entity)
            );
        }
        return self::REGISTRY[$entity];
    }

    public function getMapper(string $entity): QBMapper {
        try {
            $mapperClass = $this->getDefinition($entity)['mapper'];
            return $this->container->get($mapperClass);
        } catch (\Throwable $e) {
            header('Content-Type: text/plain; charset=utf-8');
            echo "FEHLER:\n" . $e->getMessage() . "\n\n";
            echo $e->getTraceAsString();
            exit;
        }
    }

    public function getFindByIdMethod(string $entity): string {
        return $this->getDefinition($entity)['findByIdMethod'];
    }

    public function findById(string $entity, $id): mixed {
        $mapper = $this->getMapper($entity);
        $method = $this->getFindByIdMethod($entity);

        try {
            return $mapper->$method($id);
        } catch (DoesNotExistException $e) {
            return null;
        }
    }

    public function getKnownEntities(): array {
        return array_keys(self::REGISTRY);
    }
}