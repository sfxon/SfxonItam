<?php declare(strict_types=1);

namespace OCA\SfxonItam\Service;

use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;

class FileMetaService
{
    public function __construct(
        private readonly IRootFolder $rootFolder,
    ) {
    }

    /**
     * @return array{id: int, name: string, mimetype: string, size: int, path: string}|null
     */
    public function getFileMeta(string $userId, int $fileId): ?array
    {
        try {
            $userFolder = $this->rootFolder->getUserFolder($userId);
        } catch (NotFoundException) {
            return null;
        }

        $nodes = $userFolder->getById($fileId);

        if (empty($nodes)) {
            return null;
        }

        $node = $nodes[0];

        return [
            'id' => $fileId,
            'name' => $node->getName(),
            'mimetype' => $node->getMimetype(),
            'size' => $node->getSize(),
            'path' => $userFolder->getRelativePath($node->getPath()) ?? '', // Path relative to the user path, e.g. "/ITAM-Images/foo.png".
        ];
    }
}