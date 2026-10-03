<?php declare(strict_types=1);

namespace OCA\SfxonItam\Controller;

use OCA\SfxonItam\Db\EntityMapperAbstract;
use OCA\SfxonItam\Definition\EntityDefinition;
use OCA\SfxonItam\Service\DeleteGuardService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;


abstract class AbstractItamController extends Controller
{
    protected EntityDefinition $entityDefinition;
    private EntityMapperAbstract $mapper;
    private DeleteGuardService $deleteGuardService;

    public function __construct(
        string $appName,
        IRequest $request,
        EntityDefinition $definition,
        DeleteGuardService $deleteGuardService,
        EntityMapperAbstract $mapper,
    ) {
        parent::__construct($appName, $request);
        $this->entityDefinition = $definition;
        $this->deleteGuardService = $deleteGuardService;
        $this->mapper = $mapper;
    }

    protected function doDelete(int $id): JSONResponse
    {
        $violation = $this->deleteGuardService->findViolation($this->entityDefinition, $id);

        if($violation !== null) {
            return new JSONResponse([
                'status' => 'error',
                'errors' => [$violation]
            ], Http::STATUS_UNPROCESSABLE_ENTITY); // Returns error 422
        }

        try {
            $position = $this->mapper->findById($id);
            $this->mapper->delete($position['mainData']);
        } catch(DoesNotExistException) {
            return new JSONResponse(
                ['status' => 'error', 'message' => $this->entityDefinition->label . ' not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        return new JSONResponse([
            'status' => 'ok',
        ]);
    }
}