<?php
declare(strict_types=1);
namespace OCA\SfxonItam\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string|null getName()
 * @method void setName(string|null $name)
 * @method int|null getDeviceStatusId()
 * @method void setDeviceStatusId(int|null $deviceStatusId)
 * @method int|null getPositionId()
 * @method void setPositionId(int|null $positionId)
 * @method int|null getDeviceTypeId()
 * @method void setDeviceTypeId(int|null $deviceTypeId)
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
 * @method string|null getComment()
 * @method void setComment(string|null $comment)
 */
class Device extends Entity implements \JsonSerializable {
    protected ?string $name = null;
    protected ?int $deviceStatusId = null;
    protected ?int $positionId = null;
    protected ?int $deviceTypeId = null;
    protected ?int  $itamUserId = null;
    protected ?string $serialNumber = null;
    protected ?string $serialNumber2 = null;
    protected ?string $assetNumber = null;
    protected ?int $merchantId = null;
    protected ?string $invoiceNumber = null;
    protected ?string $purchaseDate = null;
    protected ?string $customFields = null;
    protected ?string $comment = null;

    public function __construct() {
        $this->addType('id', 'integer');
        $this->addType('deviceStatusId', 'integer');
        $this->addType('positionId', 'integer');
        $this->addType('deviceTypeId', 'integer');
        $this->addType('merchantId', 'integer');
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'deviceStatusId' => $this->getDeviceStatusId(),
            'deviceTypeId' => $this->getDeviceTypeId(),
            'positionId' => $this->getPositionId(),
            'itamUserId' => $this->getItamUserId(),
            'serialNumber' => $this->getSerialNumber(),
            'serialNumber2' => $this->getSerialNumber2(),
            'assetNumber' => $this->getAssetNumber(),
            'merchantId' => $this->getMerchantId(),
            'invoiceNumber' => $this->getInvoiceNumber(),
            'purchaseDate' => $this->getPurchaseDate(),
            'customFields' => $this->getCustomFields(),
            'comment' => $this->getComment()
        ];
    }
}