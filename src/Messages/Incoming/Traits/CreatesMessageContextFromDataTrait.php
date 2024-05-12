<?php

namespace SequentSoft\ThreadFlowWhatsApp\Messages\Incoming\Traits;

use SequentSoft\ThreadFlow\Chat\MessageContext;
use SequentSoft\ThreadFlowWhatsApp\Contracts\Messages\Incoming\IncomingMessagesFactoryInterface;

trait CreatesMessageContextFromDataTrait
{
    use CreatesParticipantFromDataTrait;
    use CreatesRoomFromDataTrait;

    public static function createMessageContextFromData(
        string $channelName,
        array $data,
        IncomingMessagesFactoryInterface $factory
    ): MessageContext {
        return new MessageContext(
            $channelName,
            static::createParticipantFromData($data),
            static::createRoomFromData($data),
        );
    }
}
