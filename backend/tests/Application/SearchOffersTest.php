<?php

namespace App\Tests\Application;

use App\Application\Offer\SearchOffers;
use App\Infrastructure\Search\ElasticsearchClient;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;

/**
 * Tests d'intégration : SearchOffers use-case
 * Normes : teste le flux complet avec ES
 */
final class SearchOffersTest extends KernelTestCase
{
    private ?SearchOffers $searchOffers = null;
    private ?ElasticsearchClient $esClient = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->searchOffers = static::getContainer()->get(SearchOffers::class);
        $this->esClient = static::getContainer()->get(ElasticsearchClient::class);

        // Initialiser l'index
        $this->esClient->initializeIndex();
    }

    public function testSearchReturnsEmptyWhenNoOffersMatch(): void
    {
        $result = $this->searchOffers->search(query: 'nonexistent');

        self::assertArrayHasKey('items', $result);
        self::assertArrayHasKey('pagination', $result);
        self::assertCount(0, $result['items']);
        self::assertSame(0, $result['pagination']['total']);
    }

    public function testSearchPaginationDefaults(): void
    {
        $result = $this->searchOffers->search();

        self::assertArrayHasKey('pagination', $result);
        self::assertSame(1, $result['pagination']['page']);
        self::assertSame(20, $result['pagination']['limit']);
    }

    public function testSearchValidatesPageParam(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Page must be >= 1');

        $this->searchOffers->search(page: 0);
    }

    public function testSearchValidatesLimitParam(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Limit must be between 1 and 100');

        $this->searchOffers->search(limit: 101);
    }

    public function testSearchValidatesSortOrder(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SortOrder must be asc or desc');

        $this->searchOffers->search(sortOrder: 'invalid');
    }
}
