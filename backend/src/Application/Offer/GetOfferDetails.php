<?php

namespace App\Application\Offer;

use App\Application\Tenant\TenantContext;
use App\Entity\Offer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Workflow\WorkflowInterface;

final class GetOfferDetails
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly EntityManagerInterface $em,
        private readonly WorkflowInterface $offerPublication
    ) {
    }

    /**
     * @return array{id: string, tenantId: string, title: string, status: string, availableTransitions: list<string>}
     */
    public function get(string $offerId): array
    {
        $tenant = $this->tenantContext->getTenantId();

        try {
            $id = Ulid::fromString($offerId);
        } catch (\Throwable) {
            throw new NotFoundHttpException('Offer not found.');
        }

        $offer = $this->em->find(Offer::class, $id);
        if (!$offer instanceof Offer) {
            throw new NotFoundHttpException('Offer not found.');
        }

        $available = [];
        foreach ($this->offerPublication->getEnabledTransitions($offer) as $transition) {
            $available[] = $transition->getName();
        }

        return [
            'id' => (string) $offer->id(),
            'tenantId' => (string) $tenant,
            'title' => $offer->title(),
            'status' => $offer->status(),
            'availableTransitions' => $available,
        ];
    }
}
