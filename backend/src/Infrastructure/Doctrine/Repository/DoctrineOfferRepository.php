<?php

namespace App\Infrastructure\Doctrine\Repository;

use App\Application\Offer\Port\OfferRepositoryInterface;
use App\Entity\Offer;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineOfferRepository implements OfferRepositoryInterface
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function add(Offer $offer): void
    {
        $this->entityManager->persist($offer);
        $this->entityManager->flush();
    }

    public function listAllOrderedByTitle(): array
    {
        return $this->entityManager->getRepository(Offer::class)->findBy([], ['title' => 'ASC']);
    }
}
