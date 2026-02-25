<?php

namespace App\Interface\Workflow;

use App\Application\Messaging\Message\OfferTransitioned;
use App\Application\Messaging\Message\SendOfferPublishedNotification;
use App\Application\Tenant\TenantContext;
use App\Entity\Offer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\Event\Event;

final class WorkflowMessengerPublisherSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly MessageBusInterface $bus
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.offer_publication.completed' => 'onCompleted',
        ];
    }

    public function onCompleted(Event $event): void
    {
        $subject = $event->getSubject();
        if (!$subject instanceof Offer) {
            return;
        }

        $transition = $event->getTransition();
        if ($transition === null) {
            return;
        }

        $tenantId = (string) $this->tenantContext->getTenantId();

        // Toujours dispatcher OfferTransitioned (audit, future indexation ES, etc.)
        $this->bus->dispatch(new OfferTransitioned(
            tenantId: $tenantId,
            offerId: (string) $subject->id(),
            transition: $transition->getName(),
            toStatus: $subject->status()
        ));

        // Si l'offre passe en "published" → email async
        if ($subject->status() === 'published') {
            $this->bus->dispatch(new SendOfferPublishedNotification(
                tenantId: $tenantId,
                offerId: (string) $subject->id(),
                offerTitle: $subject->title()
            ));
        }
    }
}
