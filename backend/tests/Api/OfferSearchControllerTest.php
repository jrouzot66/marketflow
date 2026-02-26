<?php

namespace App\Tests\Api;

use App\Infrastructure\Search\ElasticsearchClient;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Ulid;

/**
 * Tests API : endpoint /api/offers/search
 */
final class OfferSearchControllerTest extends WebTestCase
{
    private ?KernelBrowser $client = null;
    private ?ElasticsearchClient $esClient = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->esClient = static::getContainer()->get(ElasticsearchClient::class);

        // Initialiser l'index (ignorez les erreurs si ES n'est pas disponible)
        try {
            $this->esClient->initializeIndex();
        } catch (\Throwable $e) {
            // ES non disponible en test
        }
    }

    public function testSearchRequiresTenantHeader(): void
    {
        $this->client->request('GET', '/api/offers/search');

        self::assertResponseStatusCodeSame(400);
    }

    public function testSearchWithValidTenantReturnsEmptyResult(): void
    {
        $tenantId = (string) new Ulid();

        $this->client->request(
            'GET',
            '/api/offers/search',
            server: ['HTTP_X_TENANT' => $tenantId]
        );

        self::assertResponseIsSuccessful();

        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($response);
        self::assertArrayHasKey('items', $response);
        self::assertArrayHasKey('pagination', $response);
        self::assertCount(0, $response['items']);
        self::assertSame(0, $response['pagination']['total']);
    }

    public function testSearchWithInvalidPageParam(): void
    {
        $tenantId = (string) new Ulid();

        $this->client->request(
            'GET',
            '/api/offers/search?page=0',
            server: ['HTTP_X_TENANT' => $tenantId]
        );

        self::assertResponseStatusCodeSame(400);
    }

    public function testSearchWithInvalidLimitParam(): void
    {
        $tenantId = (string) new Ulid();

        $this->client->request(
            'GET',
            '/api/offers/search?limit=101',
            server: ['HTTP_X_TENANT' => $tenantId]
        );

        self::assertResponseStatusCodeSame(400);
    }

    public function testSearchPaginationParams(): void
    {
        $tenantId = (string) new Ulid();

        $this->client->request(
            'GET',
            '/api/offers/search?page=2&limit=50',
            server: ['HTTP_X_TENANT' => $tenantId]
        );

        self::assertResponseIsSuccessful();

        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($response);
        self::assertSame(2, $response['pagination']['page']);
        self::assertSame(50, $response['pagination']['limit']);
    }
}
