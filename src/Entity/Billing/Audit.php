<?php

declare(strict_types=1);

namespace App\Entity\Billing;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity
 */
final class Audit
{
    private const DEFAULT_BILLING_TYPE = 'base';
    /**
     * @var UuidInterface
     *
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     */
    private $id;

    /**
     * @var int
     * @ORM\Column(type="integer", options={"default"=0})
     */
    private $usersCount = 0;

    /**
     * @var int
     * @ORM\Column(type="integer", options={"default"=0})
     */
    private $teamsCount = 0;

    /**
     * @var int
     * @ORM\Column(type="integer", options={"default"=0})
     */
    private $itemsCount = 0;

    /**
     * @var int
     * @ORM\Column(type="integer", options={"default"=0})
     */
    private $memoryUsed = 0;

    /**
     * @var string
     * @ORM\Column(type="BillingEnumType", options={"default"="base"})
     */
    private $billingType = self::DEFAULT_BILLING_TYPE;

    /**
     * @param string $label
     * @throws \Exception
     */
    public function __construct(string $label = null)
    {
        $this->id = Uuid::uuid4();
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getUsersCount(): int
    {
        return $this->usersCount;
    }

    public function setUsersCount(int $usersCount): void
    {
        $this->usersCount = $usersCount;
    }

    public function getTeamsCount(): int
    {
        return $this->teamsCount;
    }

    public function setTeamsCount(int $teamsCount): void
    {
        $this->teamsCount = $teamsCount;
    }

    public function getItemsCount(): int
    {
        return $this->itemsCount;
    }

    public function setItemsCount(int $itemsCount): void
    {
        $this->itemsCount = $itemsCount;
    }

    public function getMemoryUsed(): int
    {
        return $this->memoryUsed;
    }

    public function setMemoryUsed(int $memoryUsed): void
    {
        $this->memoryUsed = $memoryUsed;
    }

    public function getBillingType(): string
    {
        return $this->billingType;
    }

    public function setBillingType(string $billingType): void
    {
        $this->billingType = $billingType;
    }
}