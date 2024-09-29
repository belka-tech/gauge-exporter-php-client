<?php

declare(strict_types=1);

namespace Belkacar\GaugeExporterClient\Exception;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;

final class BadResponseException extends \RuntimeException implements ClientExceptionInterface
{
    private ResponseInterface $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
        parent::__construct('');
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
