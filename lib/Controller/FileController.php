<?php declare(strict_types=1);

namespace OCA\SfxonItam\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use OCA\SfxonItam\Service\FileMetaService;

/**
 * @psalm-suppress UnusedClass
 */
class FileController extends Controller
{
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly FileMetaService $fileMetaService,
        private readonly IUserSession $userSession,
    ) {
        parent::__construct($appName, $request);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/file/{fileId}/meta')]
    public function meta(int $fileId): JSONResponse
    {
        $user = $this->userSession->getUser();

        if ($user === null) {
            return new JSONResponse(
                ['status' => 'error', 'message' => 'Not authenticated'],
                Http::STATUS_UNAUTHORIZED
            );
        }

        $meta = $this->fileMetaService->getFileMeta($user->getUID(), $fileId);

        if ($meta === null) {
            return new JSONResponse(
                ['status' => 'error', 'message' => 'File not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        return new JSONResponse([
            'status' => 'success',
            'file' => $meta,
        ]);
    }
}