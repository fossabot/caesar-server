<?php

declare(strict_types=1);

namespace App\Model\DTO;

use Caesar\Entity\UserSubscriptionInterface;

class UserSubscription implements UserSubscriptionInterface
{
    /**
     * @var string|null
     */
    private $status;
    /**
     * @var int|null
     */
    private $created;
    /**
     * @var string|null
     */
    private $subscriptionId;
    /**
     * @var User
     */
    private $user;
    /**
     * @var int
     */
    private $id;
    /**
     * @var string
     */
    private $subscriptionName;
    /**
     * @var string
     */
    private $itemsLimit;
    /**
     * @var string
     */
    private $teamsLimit;
    /**
     * @var string
     */
    private $memoryLimit;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($user): void
    {
        $this->user = $user;
    }

    public function getExternalSubscriptionId(): ?string
    {
        return $this->subscriptionId;
    }

    public function setExternalSubscriptionId(?string $externalSubscriptionId): void
    {
        $this->subscriptionId = $externalSubscriptionId;
    }

    public function getCreated(): ?int
    {
        return $this->created;
    }

    public function setCreated(?int $created): void
    {
        $this->created = $created;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getItemsLimit(): ?string
    {
        return $this->itemsLimit;
    }

    public function setItemsLimit(?string $itemsLimit): void
    {
        $this->itemsLimit = $itemsLimit;
    }

    public function getTeamsLimit(): ?string
    {
        return $this->teamsLimit;
    }

    public function setTeamsLimit(?string $teamsLimit): void
    {
        $this->teamsLimit = $teamsLimit;
    }

    public function getMemoryLimit(): ?string
    {
        return $this->memoryLimit;
    }

    public function setMemoryLimit(?string $memoryLimit): void
    {
        $this->memoryLimit = $memoryLimit;
    }

    public function setSubscriptionName(?string $subscriptionName): void
    {
        $this->subscriptionName = $subscriptionName;
    }

    public function getSubscriptionName(): ?string
    {
        return $this->subscriptionName;
    }
}