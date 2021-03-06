<?php

declare(strict_types=1);

namespace App\Model\DTO;

class Message
{
    /**
     * @var string
     */
    public $email;
    /**
     * @var array
     */
    public $options = [];
    /**
     * @var string
     */
    public $code;
    /**
     * @var string
     */
    public $recipientId;

    public function __construct(string $recipientId, string $email = null, string $emailCode = null, array $options = [])
    {
        $this->email = $email;
        $this->options = $options;
        $this->code = $emailCode;
        $this->recipientId = $recipientId;
    }
}
