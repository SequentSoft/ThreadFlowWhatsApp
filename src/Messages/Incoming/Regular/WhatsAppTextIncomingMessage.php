<?php

namespace SequentSoft\ThreadFlowWhatsApp\Messages\Incoming\Regular;

use DateTimeImmutable;
use SequentSoft\ThreadFlow\Messages\Incoming\Regular\TextIncomingMessage;
use SequentSoft\ThreadFlowWhatsApp\Contracts\Messages\Incoming\CanCreateFromDataMessageInterface;
use SequentSoft\ThreadFlowWhatsApp\Contracts\Messages\Incoming\IncomingMessagesFactoryInterface;
use SequentSoft\ThreadFlowWhatsApp\Messages\Incoming\Traits\CreatesMessageContextFromDataTrait;

class WhatsAppTextIncomingMessage extends TextIncomingMessage implements CanCreateFromDataMessageInterface
{
    use CreatesMessageContextFromDataTrait;

    public static function canCreateFromData(array $data): bool
    {
        return ($data['message']['type'] ?? null) === 'text';
    }

    public static function createFromData(IncomingMessagesFactoryInterface $factory, string $channelName, array $data): self
    {
        return new static(
            id: $data['message']['id'],
            context: static::createMessageContextFromData($channelName, $data, $factory),
            timestamp: DateTimeImmutable::createFromFormat('U', $data['message']['timestamp']),
            text: $data['message']['text']['body'],
        );
    }
}
