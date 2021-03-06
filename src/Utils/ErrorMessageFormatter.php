<?php

declare(strict_types=1);

namespace App\Utils;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

class ErrorMessageFormatter
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function errorFormat(Throwable $exception, string $internalCode = null): array
    {
        $code = $this->getCode($exception, $internalCode);

        return [
            'error' => [
                'message' => $this->translator->trans($exception->getMessage()),
                'type' => get_class($exception),
                'code' => $code,
            ],
        ];
    }

    /**
     * @return int|string
     */
    private function getCode(Throwable $exception, string $internalCode = null)
    {
        switch (true) {
            case $internalCode:
                $code = $internalCode;
                break;
            case $exception instanceof HttpExceptionInterface:
                $code = $exception->getStatusCode();
                break;
            default:
                $code = $exception->getCode();
        }

        return $code;
    }
}
