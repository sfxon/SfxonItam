<?php
declare(strict_types=1);
namespace OCA\SfxonItam\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string|null getFirstname()
 * @method void setFirstname(string|null $firstname)
 * @method string|null getLastname()
 * @method void setLastname(string|null $lastname)
 * @method string|null getEmail()
 * @method void setEmail(string|null $email)
 * @method string|null getComment()
 * @method void setComment(string|null $comment)
 */
class ItamUser extends Entity implements \JsonSerializable {
    protected ?string $firstname = null;
    protected ?string $lastname = null;
    protected ?string $email = null;
    protected ?string $comment = null;

    public function __construct() {
        $this->addType('id', 'integer');
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->getId(),
            'firstname' => $this->getFirstname(),
            'lastname' => $this->getLastname(),
            'email' => $this->getEmail(),
            'comment' => $this->getComment()
        ];
    }
}