<?php
declare(strict_types=1);
namespace OCA\SfxonItam\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string|null getName()
 * @method void setName(string|null $name)
 * @method float|null getQuantity()
 * @method void setQuantity(float|null $quantity)
 * @method int|null getQuantityUnitId()
 * @method void setQuantityUnitId(int|null $quantityUnitId)
 * @method int|null getDeviceStatusId()
 * @method void setDeviceStatusId(int|null $deviceStatusId)
 * @method int|null getPositionId()
 * @method void setPositionId(int|null $positionId)
 * @method int|null getDeviceTypeId()
 * @method void setDeviceTypeId(int|null $deviceTypeId)
 * @method int getImageFileId()
 * @method void setImageFileId(int $imageFileId)
 * @method int getItamUserId()
 * @method void setItamUserId(int $itamUserId)
 * @method string|null getSerialNumber()
 * @method void setSerialNumber(string|null $serialNumber)
 * @method string|null getSerialNumber2()
 * @method void setSerialNumber2(string|null $serialNumber2)
 * @method string|null getAssetNumber()
 * @method void setAssetNumber(string|null $assetNumber)
 * @method int|null getMerchantId()
 * @method void setMerchantId(int|null $merchantId)
 * @method string|null getInvoiceNumber()
 * @method void setInvoiceNumber(string|null $invoiceNumber)
 * @method \DateTimeImmutable|null getPurchaseDate()
 * @method void setPurchaseDate(\DateTimeImmutable|null $purchaseDate)
 * @method string|null getCustomFields()
 * @method void setCustomFields(string|null $customFields)
 * @method string|null getDescription())
 * @method void setDescription(string|null $description)
 */
class Device extends Entity implements \JsonSerializable {
    protected ?string $assetNumber = null;
    protected ?string $customFields = null;
    protected ?string $description = null;
    protected ?int $deviceStatusId = null;
    protected ?int $deviceTypeId = null;
    protected ?int $imageFileId = null;
    protected ?string $invoiceNumber = null;
    protected ?int  $itamUserId = null;
    protected ?int $merchantId = null;
    protected ?string $name = null;
    protected ?int $positionId = null;
    protected ?string $purchaseDate = null;
    protected ?string $quantity = null;
    protected ?int $quantityUnitId = null;
    protected ?string $serialNumber = null;
    protected ?string $serialNumber2 = null;

    public function __construct() {
        $this->addType('id', 'integer');
        $this->addType('deviceStatusId', 'integer');
        $this->addType('positionId', 'integer');
        $this->addType('deviceTypeId', 'integer');
        $this->addType('imageFileId', 'integer');
        $this->addType('merchantId', 'integer');
        $this->addType('quantityUnitId', 'integer');
    }

    public function jsonSerialize(): array {
        return [
            'assetNumber' => $this->getAssetNumber(),
            'description' => $this->getDescription(),
            'customFields' => $this->getCustomFields(),
            'deviceStatusId' => $this->getDeviceStatusId(),
            'deviceTypeId' => $this->getDeviceTypeId(),
            'id' => $this->getId(),
            'imageFileId' => $this->getImageFileId(),
            'invoiceNumber' => $this->getInvoiceNumber(),
            'itamUserId' => $this->getItamUserId(),
            'merchantId' => $this->getMerchantId(),
            'name' => $this->getName(),
            'positionId' => $this->getPositionId(),
            'purchaseDate' => $this->getPurchaseDate(),
            'quantity' => $this->getQuantity(),
            'quantityUnitId' => $this->getQuantityUnitId(),
            'serialNumber' => $this->getSerialNumber(),
            'serialNumber2' => $this->getSerialNumber2(),
        ];
    }
}