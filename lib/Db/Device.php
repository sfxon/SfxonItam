<?php declare(strict_types=1);

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
    use TEntityWithCustomFields;

    protected ?string $assetNumber = null;
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

    /**
     * Field definitions are used to check input and create automatic forms.
     */
    public static function getFieldDefinition() {
        return [
            [
                'defaultValue' => NULL,
                'foreignEntity' => false,
                'index' => true,
                'label' => 'Identifier',
                'length' => 20,
                'name' => 'id',
                'type' => 'BIGINT',
                'requiredOnCreate' => false,
                'requiredOnUpdate' => true,
                'unique' => true,
            ],
            [
                'defaultValue' => NULL,
                'foreignEntity' => false,
                'index' => true,
                'label' => 'assetNumber',
                'length' => 300,
                'name' => 'asset_number',
                'type' => 'VARCHAR',
                'requiredOnCreate' => false,
                'requiredOnUpdate' => false,
                'unique' => false,
            ],
            [
                'defaultValue' => NULL,
                'foreignEntity' => false,
                'index' => false,
                'label' => 'Description',
                'length' => null,
                'name' => 'description',
                'type' => 'TEXT',
                'requiredOnCreate' => false,
                'requiredOnUpdate' => false,
                'unique' => false,
            ],
            [
                'defaultValue' => NULL,
                'foreignEntity' => 'device_status',
                'index' => true,
                'label' => 'Device Status',
                'length' => null,
                'name' => 'device_status_id',
                'type' => 'BIGINT',
                'requiredOnCreate' => false,
                'requiredOnUpdate' => false,
                'unique' => false,
            ],
            [
                'defaultValue' => NULL,
                'foreignEntity' => 'device_type',
                'index' => true,
                'label' => 'Device Type',
                'length' => null,
                'name' => 'device_type_id',
                'type' => 'BIGINT',
                'requiredOnCreate' => false,
                'requiredOnUpdate' => false,
                'unique' => false,
            ],
            [
                'defaultValue' => NULL,
                'foreignEntity' => 'image_file',
                'index' => true,
                'label' => 'Image File',
                'length' => null,
                'name' => 'image_file_id',
                'type' => 'BIGINT',
                'requiredOnCreate' => false,
                'requiredOnUpdate' => false,
                'unique' => false,
            ],
            [
                'defaultValue' => NULL,
                'foreignEntity' => false,
                'index' => true,
                'label' => 'Invoice Number',
                'length' => 300,
                'name' => 'invoice_number',
                'type' => 'VARCHAR',
                'requiredOnCreate' => false,
                'requiredOnUpdate' => false,
                'unique' => false,
            ],
            [
                'defaultValue' => NULL,
                'foreignEntity' => 'itam_user',
                'index' => true,
                'label' => 'Itam User',
                'length' => null,
                'name' => 'itam_user_id',
                'type' => 'BIGINT',
                'requiredOnCreate' => false,
                'requiredOnUpdate' => false,
                'unique' => false,
            ],
            [
                'defaultValue' => NULL,
                'foreignEntity' => 'merchant',
                'index' => true,
                'label' => 'Merchant',
                'length' => null,
                'name' => 'merchant_id',
                'type' => 'BIGINT',
                'requiredOnCreate' => false,
                'requiredOnUpdate' => false,
                'unique' => false,
            ],
            [
                'defaultValue' => NULL,
                'foreignEntity' => false,
                'index' => true,
                'label' => 'Name',
                'length' => 300,
                'name' => 'name',
                'type' => 'VARCHAR',
                'requiredOnCreate' => true,
                'requiredOnUpdate' => true,
                'unique' => true,
            ],
            [
                'defaultValue' => NULL,
                'foreignEntity' => 'position',
                'index' => true,
                'label' => 'Position',
                'length' => null,
                'name' => 'position_id',
                'type' => 'BIGINT',
                'requiredOnCreate' => false,
                'requiredOnUpdate' => false,
                'unique' => false,
            ],
            [
                'defaultValue' => NULL,
                'foreignEntity' => false,
                'index' => true,
                'label' => 'Purchase Date',
                'length' => null,
                'name' => 'purchase_date',
                'type' => 'DATE',
                'requiredOnCreate' => false,
                'requiredOnUpdate' => false,
                'unique' => false,
            ],
            [
                'defaultValue' => NULL,
                'foreignEntity' => false,
                'index' => true,
                'label' => 'Quantity',
                'length' => '10,4',
                'name' => 'quantity',
                'type' => 'DECIMAL',
                'requiredOnCreate' => false,
                'requiredOnUpdate' => false,
                'unique' => false,
            ],
            [
                'defaultValue' => NULL,
                'foreignEntity' => 'quantity_unit',
                'index' => true,
                'label' => 'Quantity Unit',
                'length' => null,
                'name' => 'quantity_unit_id',
                'type' => 'BIGINT',
                'requiredOnCreate' => false,
                'requiredOnUpdate' => false,
                'unique' => false,
            ],
            [
                'defaultValue' => NULL,
                'foreignEntity' => false,
                'index' => true,
                'label' => 'Serial Number',
                'length' => 300,
                'name' => 'serial_number',
                'type' => 'VARCHAR',
                'requiredOnCreate' => false,
                'requiredOnUpdate' => false,
                'unique' => false,
            ],
            [
                'defaultValue' => NULL,
                'foreignEntity' => false,
                'index' => true,
                'label' => 'Serial Number 2',
                'length' => 300,
                'name' => 'serial_number_2',
                'type' => 'VARCHAR',
                'requiredOnCreate' => false,
                'requiredOnUpdate' => false,
                'unique' => false,
            ],
        ];
    }

    /**
     * @TODO: Check, if this could be changed. It could use the definition of "getFieldDefinition", to serialize the data.
     */
    public function jsonSerialize(): array {
        return [
            'assetNumber' => $this->getAssetNumber(),
            'description' => $this->getDescription(),
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