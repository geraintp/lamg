<?php

declare(strict_types=1);

namespace App\Mail;

use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Mime\Email;
use Mailjet\Client;
use Mailjet\Resources;
use Symfony\Component\Mailer\SentMessage;

class MailjetTransport implements TransportInterface
{
    protected Client $client;

    public function __construct(string $key, string $secret)
    {
        $this->client = new Client($key, $secret, true, ['version' => 'v3.1']);
    }

    public function send(RawMessage $message, Envelope $envelope = null): ?SentMessage
    {
        if (!($message instanceof Email)) {
            throw new \LogicException('MailjetTransport only supports Email messages.');
        }

        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => config('mail.from.address'),
                        'Name' => config('mail.from.name'),
                    ],
                    'To' => array_map(fn($addr) => ['Email' => $addr->getAddress(), 'Name' => $addr->getName()], $message->getTo()),
                    'Subject' => $message->getSubject(),
                    'HTMLPart' => $message->getHtmlBody(),
                    'TextPart' => $message->getTextBody(),
                ]
            ]
        ];

        $this->client->post(Resources::$Email, ['body' => $body]);

        return null;
    }

    public function __toString(): string
    {
        return 'mailjet-api';
    }
}
