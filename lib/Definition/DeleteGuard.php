<?php declare(strict_types=1);

namespace OCA\SfxonItam\Definition;

/**
 * Prevents the deletion of an entity as long as it is referenced in another table.
 */
final class DeleteGuard
{
    /**
     * @param class-string $mapperClass Mapper of the referenced Entity (needs isEntityValueInUse())
     * @param string $column Foreign Key-Column in the referenced table.
     * @param string $message Error message for the user.
     */
    public function __construct(
        public readonly string $mapperClass,
        public readonly string $column,
        public readonly string $message,
    ) {
    }
}