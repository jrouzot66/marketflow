<?php

namespace App\Infrastructure\Doctrine\Repository;

use App\Application\Offer\OfferRepository;
use App\Domain\Offer\OfferAggregate;
use App\Domain\Offer\OfferId;
use App\Domain\Offer\OfferTitle;
use App\Domain\Tenant\TenantId;
use App\Entity\Offer as OfferEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Ulid;

final class DoctrineOfferRepository implements OfferRepository
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
    }

    public function save(OfferAggregate $offer): void
    {
        $entity = new OfferEntity(
            Ulid::fromString($offer->id()->toString()),
            $offer->tenantId()->toRfc4122(),
            $offer->title()->toString()
        );

        $this->em->persist($entity);
        $this->em->flush();
    }

    public function listByTenant(TenantId $tenantId): array
    {
        $repo = $this->em->getRepository(OfferEntity::class);

        /** @var list<OfferEntity> $entities */
        $entities = $repo->findBy(
            ['tenantId' => $tenantId->toRfc4122()],
            ['id' => 'DESC']
        );

        $items = [];
        foreach ($entities as $entity) {
            $items[] = $this->toDomain($entity, $tenantId);
        }

        return $items;
    }

    private function toDomain(OfferEntity $entity, TenantId $tenantId): OfferAggregate
    {
        return OfferAggregate::rehydrate(
            OfferId::fromString((string) $entity->id()),
            $tenantId,
            OfferTitle::fromString($entity->title())
        );
    }
}
