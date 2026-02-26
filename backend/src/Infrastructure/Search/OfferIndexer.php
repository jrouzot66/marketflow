<?php

namespace App\Infrastructure\Search;

use App\Entity\Offer;
use Psr\Log\LoggerInterface;

/**
 * Indexeur : convertit une entité Doctrine en document Elasticsearch
 * Responsabilité : hydration entité → document
 */
final class OfferIndexer
{
    public function __construct(
        private readonly ElasticsearchClient $client,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Indexe une offre depuis son entité Doctrine
     */
    public function index(Offer $offer): bool
    {
        try {
            $document = $this->entityToDocument($offer);

            return $this->client->indexOffer(
                $offer->id()->toString(),
                $offer->tenantId(),  // ← Juste la valeur stockée en DB (RFC4122)
                $document
            );
        } catch (\Throwable $e) {
            $this->logger->error('Indexing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    private function rfcToUlid(string $rfc4122): string
    {
        try {
            // RFC4122 UUID → ULID
            return \Symfony\Component\Uid\Ulid::fromString($rfc4122)->toBase32();
        } catch (\Throwable $e) {
            return $rfc4122; // Fallback
        }
    }

    /**
     * Supprime une offre de l'index
     */
    public function unindex(string $offerId): bool
    {
        return $this->client->deleteOffer($offerId);
    }

    /**
     * Convertit une entité Doctrine en document Elasticsearch
     * @return array<string, mixed>
     */
    private function entityToDocument(Offer $offer): array
    {
        return [
            'title' => $offer->title(),
            'status' => $offer->status(),
        ];
    }
}
