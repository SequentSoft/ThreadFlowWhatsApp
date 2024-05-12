<?php

namespace SequentSoft\ThreadFlowWhatsApp\Contracts\HttpClient;

interface HttpClientFactoryInterface
{
    public function create(string $token, string $fromPhoneNumberId): HttpClientInterface;
}
