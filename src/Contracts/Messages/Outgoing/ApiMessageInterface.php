<?php

namespace SequentSoft\ThreadFlowWhatsApp\Contracts\Messages\Outgoing;

use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\BasicOutgoingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Page\PageInterface;
use SequentSoft\ThreadFlowWhatsApp\Contracts\HttpClient\HttpClientInterface;

interface ApiMessageInterface
{
    public static function canCreateFromMessage(BasicOutgoingMessageInterface $outgoingMessage): bool;

    public static function createFromMessage(
        BasicOutgoingMessageInterface $outgoingMessage,
        ?PageInterface $contextPage = null
    ): static;

    public function sendVia(HttpClientInterface $client): array;
}
