<?php

namespace SequentSoft\ThreadFlowWhatsApp\Messages\Incoming\Regular;

use DateTimeImmutable;
use SequentSoft\ThreadFlow\Contracts\Keyboard\ButtonInterface;
use SequentSoft\ThreadFlow\Keyboard\Button;
use SequentSoft\ThreadFlow\Messages\Incoming\Regular\ClickIncomingMessage;
use SequentSoft\ThreadFlowWhatsApp\Contracts\Messages\Incoming\CanCreateFromDataMessageInterface;
use SequentSoft\ThreadFlowWhatsApp\Contracts\Messages\Incoming\IncomingMessagesFactoryInterface;
use SequentSoft\ThreadFlowWhatsApp\Messages\Incoming\Traits\CreatesMessageContextFromDataTrait;

class WhatsAppClickedIncomingMessage extends ClickIncomingMessage implements CanCreateFromDataMessageInterface
{
    use CreatesMessageContextFromDataTrait;

    public static function canCreateFromData(array $data): bool
    {
        return ($data['message']['type'] ?? null) === 'interactive'
            && ($data['message']['interactive']['type'] ?? null) === 'button_reply';
    }

    public static function createFromData(IncomingMessagesFactoryInterface $factory, string $channelName, array $data): self
    {
        return new static(
            id: $data['message']['id'],
            context: static::createMessageContextFromData($channelName, $data, $factory),
            timestamp: DateTimeImmutable::createFromFormat('U', $data['message']['timestamp']),
            button: Button::text(
                title: $data['message']['interactive']['button_reply']['title'],
                key: $data['message']['interactive']['button_reply']['id'],
            )
        );
    }

    public function setButton(ButtonInterface $button): self
    {
        $this->button = $button;

        return $this;
    }
}
