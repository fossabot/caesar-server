<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Table
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ItemUpdate
{
    /**
     * @var UuidInterface
     *
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    protected $secret;

    /**
     * @var Item
     *
     * @ORM\OneToOne(targetEntity="App\Entity\Item", inversedBy="update")
     */
    protected $item;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    protected $updatedBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $lastUpdated;

    public function __construct(Item $item, User $user)
    {
        $this->id = Uuid::uuid4();
        $this->updatedBy = $user;

        $this->item = $item;
        $item->setUpdate($this);
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getSecret(): ?string
    {
        return $this->secret;
    }

    public function setSecret(string $secret)
    {
        $this->secret = $secret;
    }

    public function getLastUpdated(): \DateTime
    {
        return $this->lastUpdated;
    }

    /**
     * @ORM\PreUpdate
     * @ORM\PrePersist
     */
    public function refreshLastUpdated()
    {
        $this->lastUpdated = new \DateTime();
    }

    public function getItem(): Item
    {
        return $this->item;
    }

    public function getUpdatedBy(): User
    {
        return $this->updatedBy;
    }

    /**
     * @param Item $item|null
     */
    public function setItem(?Item $item): void
    {
        $this->item = $item;
    }

    public function getUser(): User
    {
        return $this->getUpdatedBy();
    }

    public function setUser(?User $user): void
    {
        if ($user) {
            $this->updatedBy = $user;
        }
    }
}
