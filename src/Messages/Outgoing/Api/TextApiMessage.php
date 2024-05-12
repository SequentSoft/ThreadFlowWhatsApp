<?php

namespace SequentSoft\ThreadFlowWhatsApp\Messages\Outgoing\Api;

use Illuminate\Support\Facades\Log;
use SequentSoft\ThreadFlow\Contracts\Keyboard\Buttons\TextButtonInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\BasicOutgoingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\Regular\HtmlOutgoingMessageInterface;
use SequentSoft\ThreadFlow\Messages\Outgoing\Regular\HtmlOutgoingMessage;
use SequentSoft\ThreadFlow\Messages\Outgoing\Regular\TextOutgoingMessage;
use SequentSoft\ThreadFlowWhatsApp\Contracts\HttpClient\HttpClientInterface;
use League\HTMLToMarkdown\HtmlConverter;

class TextApiMessage extends BaseApiMessage
{
    public static function canCreateFromMessage(BasicOutgoingMessageInterface $outgoingMessage): bool
    {
        return $outgoingMessage instanceof TextOutgoingMessage
            || $outgoingMessage instanceof HtmlOutgoingMessage;
    }

    protected function send(HttpClientInterface $client, BasicOutgoingMessageInterface $outgoingMessage, array $data): array
    {
        /** @var TextOutgoingMessage|HtmlOutgoingMessage $outgoingMessage */
        $keyboard = $outgoingMessage->getKeyboard();

        if ($outgoingMessage instanceof HtmlOutgoingMessageInterface) {
            $converter = new HtmlConverter();
            $converter->getConfig()->setOption('bold_style', '*');
            $converter->getConfig()->setOption('italic_style', '_');
            $text = $converter->convert($outgoingMessage->getHtml());

            Log::debug('HTML converted to markdown', ['text' => $text]);
        } else {
            // @var TextOutgoingMessage $outgoingMessage
            $text = $outgoingMessage->getText();
        }

        if (! $keyboard) {
            return $client->postJson(
                'messages',
                [
                    'messaging_product' => 'whatsapp',
                    'to' => $outgoingMessage->getContext()->getParticipant()->getId(),
                    'type' => 'text',
                    'text' => [
                        'body' => $text,
                    ],
                ]
            )->getParsedDataResult();
        }

        $buttons = [];

        foreach ($keyboard->getRows() as $row) {
            foreach ($row->getButtons() as $button) {
                if ($button instanceof TextButtonInterface) {
                    $buttons[] = [
                        'type' => 'reply',
                        'reply' => [
                            'id' => $button->getCallbackData(),
                            'title' => $button->getTitle(),
                        ],
                    ];
                }
            }
        }

        return $client->postJson(
            'messages',
            [
                'messaging_product' => 'whatsapp',
                'to' => $outgoingMessage->getContext()->getParticipant()->getId(),
                'type' => 'interactive',
                'interactive' => [
                    'type' => 'button',
                    //                    'header' => [
                    //                        'type' => 'text',
                    //                        'text' => 'The header',
                    //                    ],
                    'body' => [
                        'text' => $text,
                    ],
                    //                    'footer' => [
                    //                        'text' => 'The footer',
                    //                    ],
                    'action' => [
                        'buttons' => $buttons,
                    ],
                ],
            ]
        )->getParsedDataResult();
    }
}
