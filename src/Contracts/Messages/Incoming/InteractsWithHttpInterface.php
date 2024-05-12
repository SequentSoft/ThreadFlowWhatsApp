<?php

namespace SequentSoft\ThreadFlowWhatsApp\Contracts\Messages\Incoming;

use SequentSoft\ThreadFlowWhatsApp\Contracts\HttpClient\HttpClientFactoryInterface;

interface InteractsWithHttpInterface
{
    public function getApiToken(): string;

    public function setApiToken(string $apiToken): void;

    public function setHttpClientFactory(HttpClientFactoryInterface $httpClientFactory): void;

    public function getHttpClientFactory(): HttpClientFactoryInterface;
}
