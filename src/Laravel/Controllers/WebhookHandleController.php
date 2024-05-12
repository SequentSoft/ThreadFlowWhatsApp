<?php

namespace SequentSoft\ThreadFlowWhatsApp\Laravel\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use SequentSoft\ThreadFlow\Contracts\Config\ConfigInterface;
use SequentSoft\ThreadFlow\Laravel\Facades\ThreadFlowBot;
use SequentSoft\ThreadFlowWhatsApp\DataFetchers\InvokableDataFetcher;
use SequentSoft\ThreadFlowWhatsApp\WhatsAppChannel;

class WebhookHandleController
{
    public function __invoke(Request $request): JsonResponse|string
    {
        return $this->handle($request);
    }

    protected function isSubscribingToWebhook(Request $request): bool
    {
        return $request->has('hub_mode')
            && $request->get('hub_mode') === 'subscribe'
            && $request->has('hub_challenge')
            && $request->has('hub_verify_token');
    }

    protected function verifySubscribeSecretToken(Request $request, ConfigInterface $config): bool
    {
        return $config->get('webhook_verify_token') === $request->get('hub_verify_token');
    }

    protected function verifyPayloadSignature(Request $request, string $appSecret): bool
    {
        $secretToken = $request->header('x-hub-signature-256');

        if (! $secretToken) {
            return false;
        }

        $payload = $request->getContent();

        $calculatedSecretToken = 'sha256=' . hash_hmac('sha256', $payload, $appSecret);

        return hash_equals($calculatedSecretToken, $secretToken);
    }

    public function handle(Request $request): JsonResponse|string
    {
        try {
            $channel = ThreadFlowBot::channel(
                $request->get('channel')
            );
        } catch (Exception $e) {
            return response()->json(array_filter([
                'status' => 'ignored',
                'reason' => 'Invalid channel',

                // display only if debug mode is enabled
                'message' => config('app.debug') ? $e->getMessage() : null,
            ]));
        }

        if (! $channel instanceof WhatsAppChannel) {
            return response()->json([
                'status' => 'ignored',
                'reason' => 'Invalid channel driver',
            ]);
        }

        $config = $channel->getConfig();

        // verify the webhook subscription
        if ($this->isSubscribingToWebhook($request)) {
            $isSubscribed = $this->verifySubscribeSecretToken($request, $config);

            return $isSubscribed
                ? $request->get('hub_challenge')
                : response()->json(['status' => 'ignored', 'reason' => 'Invalid secret token']);
        }

        $appSecret = $config->get('app_secret');

        // verify the payload signature
        if (! $this->verifyPayloadSignature($request, $appSecret)) {
            return response()->json([
                'status' => 'ignored',
                'reason' => 'Invalid secret token',
            ]);
        }

        $invokableDataFetcher = new InvokableDataFetcher();

        $channel->listen($invokableDataFetcher);

        $invokableDataFetcher($request->all());

        return response()->json([
            'status' => 'handled',
        ]);
    }
}
