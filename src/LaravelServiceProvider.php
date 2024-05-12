<?php

namespace SequentSoft\ThreadFlowWhatsApp;

use Illuminate\Support\ServiceProvider;
use SequentSoft\ThreadFlow\Contracts\Channel\ChannelManagerInterface;
use SequentSoft\ThreadFlow\Contracts\Config\ConfigInterface;
use SequentSoft\ThreadFlow\Contracts\Dispatcher\DispatcherInterface;
use SequentSoft\ThreadFlow\Contracts\Events\EventBusInterface;
use SequentSoft\ThreadFlow\Contracts\Session\SessionStoreInterface;
use SequentSoft\ThreadFlowWhatsApp\Contracts\HttpClient\HttpClientFactoryInterface;
use SequentSoft\ThreadFlowWhatsApp\Contracts\Messages\Incoming\IncomingMessagesFactoryInterface;
use SequentSoft\ThreadFlowWhatsApp\Contracts\Messages\Outgoing\OutgoingApiMessageFactoryInterface;
use SequentSoft\ThreadFlowWhatsApp\HttpClient\GuzzleHttpClientFactory;
use SequentSoft\ThreadFlowWhatsApp\Laravel\Controllers\WebhookHandleController;
use SequentSoft\ThreadFlowWhatsApp\Messages\Incoming\IncomingMessagesFactory;
use SequentSoft\ThreadFlowWhatsApp\Messages\Incoming\Regular\WhatsAppClickedIncomingMessage;
use SequentSoft\ThreadFlowWhatsApp\Messages\Incoming\Regular\WhatsAppTextIncomingMessage;
use SequentSoft\ThreadFlowWhatsApp\Messages\Incoming\Regular\WhatsAppUnknownIncomingMessage;
use SequentSoft\ThreadFlowWhatsApp\Messages\Outgoing\Api\TextApiMessage;
use SequentSoft\ThreadFlowWhatsApp\Messages\Outgoing\OutgoingApiMessageFactory;

class LaravelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(IncomingMessagesFactoryInterface::class, IncomingMessagesFactory::class);
        $this->app->singleton(OutgoingApiMessageFactoryInterface::class, OutgoingApiMessageFactory::class);
        $this->app->singleton(HttpClientFactoryInterface::class, GuzzleHttpClientFactory::class);
    }

    protected function getDefaultIncomingMessagesTypes(): array
    {
        return [
            WhatsAppTextIncomingMessage::class,
            WhatsAppClickedIncomingMessage::class,
        ];
    }

    protected function getDefaultOutgoingApiMessagesTypes(): array
    {
        return [
            TextApiMessage::class,
        ];
    }

    protected function bootWebhookRoutes(): void
    {
        foreach ($this->app->get('config')->get('thread-flow.channels', []) as $channelData) {
            $driver = $channelData['driver'] ?? null;
            $webhookUrl = ltrim(parse_url($channelData['webhook_url'] ?? '', PHP_URL_PATH), '/');

            if ($driver === 'whatsapp' && $webhookUrl) {
                $this->app->get('router')->any(
                    $webhookUrl,
                    [WebhookHandleController::class, 'handle']
                );
            }
        }
    }

    public function boot(): void
    {
        $this->app->afterResolving(
            ChannelManagerInterface::class,
            fn (ChannelManagerInterface $channelManager) => $channelManager->registerChannelDriver(
                'whatsapp',
                fn (
                    string $channelName,
                    ConfigInterface $config,
                    SessionStoreInterface $sessionStore,
                    DispatcherInterface $dispatcher,
                    EventBusInterface $eventBus
                ) => new WhatsAppChannel(
                    $channelName,
                    $config,
                    $sessionStore,
                    $dispatcher,
                    $eventBus,
                    $this->app->make(HttpClientFactoryInterface::class),
                    $this->app->make(IncomingMessagesFactoryInterface::class),
                    $this->app->make(OutgoingApiMessageFactoryInterface::class),
                )
            )
        );

        $this->app->afterResolving(
            IncomingMessagesFactoryInterface::class,
            fn (IncomingMessagesFactory $factory) => $factory
                ->addMessageTypeClass($this->getDefaultIncomingMessagesTypes())
                ->registerFallbackMessage(WhatsAppUnknownIncomingMessage::class)
        );

        $this->app->afterResolving(
            OutgoingApiMessageFactoryInterface::class,
            fn (OutgoingApiMessageFactoryInterface $factory) => $factory
                ->addApiMessageTypeClass($this->getDefaultOutgoingApiMessagesTypes())
        );

        $this->bootWebhookRoutes();
    }
}
