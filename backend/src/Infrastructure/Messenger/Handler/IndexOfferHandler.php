<?php

namespace App\Infrastructure\Messenger\Handler;

use App\Entity\Offer;
use App\Infrastructure\Search\ElasticsearchClient;
use App\Infrastructure\Search\OfferIndexer;
use App\Infrastructure\Messenger\Message\IndexOfferMessage;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler : indexe une offre dans Elasticsearch
 * Normes : async, idempotent, logged
 */
#[AsMessageHandler]
final class IndexOfferHandler
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly OfferIndexer $indexer,
        private readonly ElasticsearchClient $esClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(IndexOfferMessage $message): void
    {
        try {
            // Initialiser l'index si nécessaire (idempotent)
            $this->esClient->initializeIndex();

            // Récupérer l'offre depuis la DB
            $offer = $this->em->getRepository(Offer::class)->find($message->offerId);

            if ($offer === null) {
                $this->logger->warning('Offer not found for indexing', ['id' => $message->offerId]);
                return;
            }

            // Indexer dans ES
            if (!$this->indexer->index($offer)) {
                throw new \RuntimeException('Failed to index offer in Elasticsearch');
            }

            $this->logger->info('Offer indexed successfully', ['id' => $message->offerId]);
        } catch (\Throwable $e) {
            $this->logger->error('Indexing handler failed', [
                'id' => $message->offerId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
