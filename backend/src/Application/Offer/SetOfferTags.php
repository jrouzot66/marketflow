<?php

namespace App\Application\Offer;

use App\Application\Tenant\TenantContext;
use App\Domain\Offer\TagIterator;
use App\Entity\Offer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Ulid;

final class SetOfferTags
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly EntityManagerInterface $em,
        private readonly TagIterator $tagIterator
    ) {
    }

    /**
     * @return list<string>
     */
    public function setFromRawInput(string $offerId, string $rawTagInput): array
    {
        $this->tenantContext->getTenantId();

        try {
            $id = Ulid::fromString($offerId);
        } catch (\Throwable) {
            throw new NotFoundHttpException('Offer not found.');
        }

        $offer = $this->em->find(Offer::class, $id);
        if (!$offer) {
            throw new NotFoundHttpException('Offer not found.');
        }

        // Parse et normalise
        $tags = $this->tagIterator->parse($rawTagInput);

        // Met à jour l'entité Doctrine (passe array de strings)
        $offer->setTags($tags->toStrings());
        $this->em->flush();

        return $tags->toStrings();
    }
}
