<?php

declare(strict_types=1);

namespace App\Model\DTO;

final class BillingViolation
{
    /**
     * @var string
     */
    private $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}