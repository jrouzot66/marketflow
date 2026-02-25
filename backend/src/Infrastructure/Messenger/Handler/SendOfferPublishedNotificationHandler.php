<?php

namespace App\Infrastructure\Messenger\Handler;

use App\Application\Messaging\Message\SendOfferPublishedNotification;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
final class SendOfferPublishedNotificationHandler
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(SendOfferPublishedNotification $message): void
    {
        $email = (new Email())
            ->from('noreply@marketflow.local')
            ->to('publisher@marketflow.local')
            ->subject(sprintf('Offer "%s" has been published', $message->offerTitle))
            ->html($this->buildHtmlContent($message));

        try {
            $this->mailer->send($email);
            $this->logger->info('Offer published notification sent', [
                'tenantId' => $message->tenantId,
                'offerId' => $message->offerId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send offer published notification', [
                'tenantId' => $message->tenantId,
                'offerId' => $message->offerId,
                'error' => $e->getMessage(),
            ]);
            throw $e; // pour que Messenger le rejoue
        }
    }

    private function buildHtmlContent(SendOfferPublishedNotification $message): string
    {
        return sprintf(
            '<h2>Offer Published</h2>'
            . '<p>The offer <strong>%s</strong> (ID: %s) has been successfully published.</p>'
            . '<p><strong>Tenant:</strong> %s</p>',
            htmlspecialchars($message->offerTitle),
            htmlspecialchars($message->offerId),
            htmlspecialchars($message->tenantId)
        );
    }
}
