<?php

namespace SequentSoft\ThreadFlowWhatsApp\Contracts\HttpClient;

interface HttpClientInterface
{
    public function getBaseUri(string $fromPhoneNumberId): string;

    public function postJson(string $endpoint, array $payload): ResponseInterface;

    public function postMultipart(string $endpoint, array $payload): ResponseInterface;
}
