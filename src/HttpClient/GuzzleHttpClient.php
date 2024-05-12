<?php

namespace SequentSoft\ThreadFlowWhatsApp\HttpClient;

use GuzzleHttp\Client;
use SequentSoft\ThreadFlowWhatsApp\Contracts\HttpClient\HttpClientInterface;
use SequentSoft\ThreadFlowWhatsApp\Contracts\HttpClient\ResponseInterface;

class GuzzleHttpClient implements HttpClientInterface
{
    protected Client $client;

    public function __construct(protected string $token, string $fromPhoneNumberId)
    {
        $this->client = new Client([
            'base_uri' => $this->getBaseUri($fromPhoneNumberId),
        ]);
    }

    public function getBaseUri(string $fromPhoneNumberId): string
    {
        return "https://graph.facebook.com/v18.0/{$fromPhoneNumberId}/";
    }

    public function postJson(string $endpoint, array $payload): ResponseInterface
    {
        $response = $this->client->post($endpoint, [
            'json' => $payload,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
            ],
        ]);

        return new Response(
            $response->getBody()->getContents(),
            $response->getStatusCode()
        );
    }

    public function postMultipart(string $endpoint, array $payload): ResponseInterface
    {
        $response = $this->client->post($endpoint, [
            'multipart' => $payload,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
            ],
        ]);

        return new Response(
            $response->getBody()->getContents(),
            $response->getStatusCode()
        );
    }
}
