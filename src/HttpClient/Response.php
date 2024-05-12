<?php

namespace SequentSoft\ThreadFlowWhatsApp\HttpClient;

use SequentSoft\ThreadFlowWhatsApp\Contracts\HttpClient\ResponseInterface;

class Response implements ResponseInterface
{
    public function __construct(
        protected string $rawData,
        protected int $statusCode,
    ) {
    }

    public function getRawData(): string
    {
        return $this->rawData;
    }

    public function getParsedData(): array
    {
        return json_decode(
            $this->rawData,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    public function getParsedDataResult(): array
    {
        return $this->getParsedData();
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
