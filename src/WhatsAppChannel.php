<?php

namespace SequentSoft\ThreadFlowWhatsApp;

use Illuminate\Support\Facades\Log;
use SequentSoft\ThreadFlow\Channel\Channel;
use SequentSoft\ThreadFlow\Contracts\Config\ConfigInterface;
use SequentSoft\ThreadFlow\Contracts\DataFetchers\DataFetcherInterface;
use SequentSoft\ThreadFlow\Contracts\Dispatcher\DispatcherInterface;
use SequentSoft\ThreadFlow\Contracts\Events\EventBusInterface;
use SequentSoft\ThreadFlow\Contracts\Keyboard\SimpleKeyboardInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Incoming\BasicIncomingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Incoming\Regular\ClickIncomingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Incoming\Regular\TextIncomingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\BasicOutgoingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Page\PageInterface;
use SequentSoft\ThreadFlow\Contracts\Session\SessionInterface;
use SequentSoft\ThreadFlow\Contracts\Session\SessionStoreInterface;
use SequentSoft\ThreadFlow\Keyboard\Buttons\TextButton;
use SequentSoft\ThreadFlowWhatsApp\Contracts\HttpClient\HttpClientFactoryInterface;
use SequentSoft\ThreadFlowWhatsApp\Contracts\HttpClient\HttpClientInterface;
use SequentSoft\ThreadFlowWhatsApp\Contracts\Messages\Incoming\IncomingMessagesFactoryInterface;
use SequentSoft\ThreadFlowWhatsApp\Contracts\Messages\Incoming\InteractsWithHttpInterface;
use SequentSoft\ThreadFlowWhatsApp\Contracts\Messages\Outgoing\OutgoingApiMessageFactoryInterface;
use SequentSoft\ThreadFlowWhatsApp\Messages\Incoming\Regular\WhatsAppClickedIncomingMessage;

class WhatsAppChannel extends Channel
{
    public function __construct(
        protected string $channelName,
        protected ConfigInterface $config,
        protected SessionStoreInterface $sessionStore,
        protected DispatcherInterface $dispatcher,
        protected EventBusInterface $eventBus,
        protected HttpClientFactoryInterface $httpClientFactory,
        protected IncomingMessagesFactoryInterface $messagesFactory,
        protected OutgoingApiMessageFactoryInterface $outgoingApiMessageFactory,
    ) {
        parent::__construct(
            $channelName,
            $config,
            $sessionStore,
            $dispatcher,
            $eventBus,
        );
    }

    protected function getFromPhoneNumberId(): string
    {
        return $this->config->get('from_phone_number_id');
    }

    protected function getApiToken(): string
    {
        return $this->config->get('api_token');
    }

    protected function handleIncomingMessages(array $data): void
    {
        Log::info('WhatsAppChannel::handleIncomingMessages', $data);

        if (($data['field'] ?? null) !== 'messages') {
            return;
        }

        $contacts = $data['value']['contacts'] ?? [];

        //        foreach ($data['value']['statuses'] ?? [] as $statusData) {
        //            {
        //                                "id": "wamid.HBgMMzgwOTkxODI2MzkwFQIAERgSMkUyMjcxRjIwMjFBMDg2NEZFAA==",
        //                                "status": "read",
        //                                "timestamp": "1708649978",
        //                                "recipient_id": "380991826390"
        //                            }
        //        }

        foreach ($data['value']['messages'] ?? [] as $messageData) {
            $contact = null;

            foreach ($contacts as $contactItem) {
                if ($contactItem['wa_id'] === $messageData['from']) {
                    $contact = $contactItem;
                    break;
                }
            }

            $message = $this->messagesFactory->make($this->channelName, [
                'contact' => $contact,
                'message' => $messageData,
            ]);

            if ($message instanceof InteractsWithHttpInterface) {
                $message->setApiToken($this->getApiToken());
                $message->setHttpClientFactory($this->httpClientFactory);
            }

            $this->incoming($message);
        }
    }

    public function listen(DataFetcherInterface $fetcher): void
    {
        $fetcher->fetch(function (array $update) {
            foreach ($update['entry'] ?? [] as $entryItem) {
                foreach ($entryItem['changes'] as $changesItem) {
                    $this->handleIncomingMessages($changesItem);
                }
            }
        });
    }

    protected function getHttpClient(): HttpClientInterface
    {
        return $this->httpClientFactory->create(
            $this->getApiToken(),
            $this->getFromPhoneNumberId()
        );
    }

    protected function prepareIncomingKeyboardClick(
        BasicIncomingMessageInterface $message,
        SessionInterface $session,
        SimpleKeyboardInterface $keyboard
    ): ?ClickIncomingMessageInterface {
        if ($message instanceof WhatsAppClickedIncomingMessage) {
            $button = $message->getButton();

            if ($button instanceof TextButton) {
                if ($newButton = $keyboard->getButtonByKey($button->getCallbackData())) {
                    $message->setButton($newButton);

                    Log::info('Set new button', [
                        'newButton' => serialize($newButton),
                    ]);
                }
            }

            return $message;
        }


        if (! $message instanceof TextIncomingMessageInterface) {
            return null;
        }

        if (! $button = $keyboard->getButtonByTitle($message->getText())) {
            return null;
        }

        if ($button->isAnswerAsText()) {
            return null;
        }

        return new WhatsAppClickedIncomingMessage(
            id: $message->getId(),
            context: $message->getContext(),
            timestamp: $message->getTimestamp(),
            button: $button,
        );
    }

    protected function outgoing(
        BasicOutgoingMessageInterface $message,
        ?SessionInterface $session,
        ?PageInterface $contextPage
    ): BasicOutgoingMessageInterface {
        $apiMessage = $this->outgoingApiMessageFactory
            ->make($message, $contextPage);

        $result = $apiMessage->sendVia(
            $this->getHttpClient()
        );

        $message->setId($result['messages'][0]['id'] ?? null);

        return $message;
    }
}
