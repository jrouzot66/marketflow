<?php

namespace App\Infrastructure\Search;

use Elastic\Elasticsearch\Client;
use Psr\Log\LoggerInterface;

/**
 * Wrapper autour du client Elasticsearch
 * Responsabilité unique : opérations CRUD sur ES
 */
final class ElasticsearchClient
{
    private const INDEX_OFFERS = 'offers';

    public function __construct(
        private readonly Client $client,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Index un document offre
     */
    public function indexOffer(string $offerId, string $tenantId, array $document): bool
    {
        try {
            // Créer index simple s'il n'existe pas
            if (!$this->client->indices()->exists(['index' => self::INDEX_OFFERS])) {
                $this->client->indices()->create([
                    'index' => self::INDEX_OFFERS,
                ]);
                $this->logger->info('Index created', ['index' => self::INDEX_OFFERS]);
            }

            // Indexer le document
            $this->client->index([
                'index' => self::INDEX_OFFERS,
                'id' => $offerId,
                'body' => array_merge($document, [
                    'tenant_id' => $tenantId,
                    'indexed_at' => (new \DateTimeImmutable())->format('Y-m-d\TH:i:s.u\Z'),
                ]),
            ]);

            $this->logger->info('Offer indexed', ['id' => $offerId, 'tenant' => $tenantId]);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to index offer', [
                'id' => $offerId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Supprime un document offre de l'index
     */
    public function deleteOffer(string $offerId): bool
    {
        try {
            $this->client->delete([
                'index' => self::INDEX_OFFERS,
                'id' => $offerId,
            ]);
            $this->logger->info('Offer deleted from index', ['id' => $offerId]);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to delete offer from index', [
                'id' => $offerId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Exécute une recherche avec filtres obligatoires multi-tenant
     * @return array{total: int, hits: list<array{_id: string, _score: float, _source: array}>}
     */
    public function search(string $tenantId, array $query): array
    {
        try {
            // Construire la clause 'must' : ajouter le full-text s'il existe
            $must = [];
            if (!empty($query['must'])) {
                $must = $query['must'];
            }

            // Construire la clause 'filter' : ajouter toujours le tenant_id
            $filter = $query['filter'] ?? [];
            $filter[] = ['term' => ['tenant_id' => $tenantId]];

            $result = $this->client->search([
                'index' => self::INDEX_OFFERS,
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => $must ?: [['match_all' => []]],  // Si pas de must, retourner tous du tenant
                            'filter' => $filter,  // Toujours filtrer par tenant
                        ],
                    ],
                    'from' => $query['from'] ?? 0,
                    'size' => $query['size'] ?? 20,
                    'sort' => $query['sort'] ?? [['_score' => ['order' => 'desc']]],
                ],
            ]);

            return [
                'total' => $result['hits']['total']['value'] ?? 0,
                'hits' => $result['hits']['hits'] ?? [],
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Search failed', [
                'tenant' => $tenantId,
                'error' => $e->getMessage(),
            ]);
            return ['total' => 0, 'hits' => []];
        }
    }

    private function convertUlidToRfc4122(string $tenantId): string
    {
        try {
            return \Symfony\Component\Uid\Ulid::fromString($tenantId)->toRfc4122();
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to convert ULID to RFC4122', ['tenantId' => $tenantId]);
            return $tenantId; // Fallback
        }
    }

    /**
     * Initialise l'index avec settings simples
     */
    public function initializeIndex(): void
    {
        try {
            if ($this->client->indices()->exists(['index' => self::INDEX_OFFERS])) {
                $this->logger->info('Index already exists', ['index' => self::INDEX_OFFERS]);
                return;
            }

            $this->client->indices()->create([
                'index' => self::INDEX_OFFERS,
                'body' => [
                    'settings' => [
                        'number_of_shards' => 1,
                        'number_of_replicas' => 0,
                    ],
                ],
            ]);

            $this->logger->info('Index created successfully', ['index' => self::INDEX_OFFERS]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to initialize index', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
