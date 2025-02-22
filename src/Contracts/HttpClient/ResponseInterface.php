<?php

namespace SequentSoft\ThreadFlowWhatsApp\Contracts\HttpClient;

interface ResponseInterface
{
    public function getRawData(): string;
    public function getParsedDataResult(): mixed;
    public function getParsedData(): array;
    public function getStatusCode(): int;
}
