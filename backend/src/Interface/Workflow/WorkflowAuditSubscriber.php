<?php

namespace App\Interface\Workflow;

use App\Application\Tenant\TenantContext;
use App\Entity\Offer;
use App\Entity\WorkflowAudit;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

final class WorkflowAuditSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly EntityManagerInterface $em
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

        $froms = $event->getMarking()->getPlaces();
        $fromPlace = array_key_first($froms);

        $toPlace = method_exists($subject, 'status') ? $subject->status() : null;
        if (!is_string($toPlace) || $toPlace === '') {
            return;
        }

        $tenantId = $this->tenantContext->getTenantId()->toRfc4122();

        $audit = new WorkflowAudit(
            tenantId: $tenantId,
            subjectType: 'offer',
            subjectId: (string) $subject->id(),
            transition: $transition->getName(),
            fromPlace: is_string($fromPlace) ? $fromPlace : null,
            toPlace: $toPlace,
            actor: null
        );

        $this->em->persist($audit);
        $this->em->flush();
    }
}
