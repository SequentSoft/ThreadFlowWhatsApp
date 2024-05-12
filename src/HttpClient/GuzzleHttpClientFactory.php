<?php

namespace SequentSoft\ThreadFlowWhatsApp\HttpClient;

use SequentSoft\ThreadFlowWhatsApp\Contracts\HttpClient\HttpClientFactoryInterface;
use SequentSoft\ThreadFlowWhatsApp\Contracts\HttpClient\HttpClientInterface;

class GuzzleHttpClientFactory implements HttpClientFactoryInterface
{
    public function create(string $token, string $fromPhoneNumberId): HttpClientInterface
    {
        return new GuzzleHttpClient($token, $fromPhoneNumberId);
    }
}
