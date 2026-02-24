<?php

namespace App\Application\Offer;

use App\Application\Tenant\TenantContext;
use App\Entity\Offer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Workflow\WorkflowInterface;

final class ApplyOfferTransition
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly EntityManagerInterface $em,
        private readonly WorkflowInterface $offerPublication
    ) {
    }

    public function apply(string $offerId, string $transition): Offer
    {
        // ensures tenant exists (and Doctrine tenant filter is already active on /api/*)
        $this->tenantContext->getTenantId();

        try {
            $id = Ulid::fromString($offerId);
        } catch (\Throwable) {
            throw new NotFoundHttpException('Offer not found.');
        }

        $offer = $this->em->find(Offer::class, $id);
        if (!$offer instanceof Offer) {
            throw new NotFoundHttpException('Offer not found.');
        }

        if (!$this->offerPublication->can($offer, $transition)) {
            throw new BadRequestHttpException(sprintf(
                'Transition "%s" not allowed from "%s".',
                $transition,
                $offer->status()
            ));
        }

        $this->offerPublication->apply($offer, $transition);
        $this->em->flush();

        return $offer;
    }
}
