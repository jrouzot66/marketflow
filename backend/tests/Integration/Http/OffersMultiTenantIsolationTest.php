<?php

namespace App\Tests\Integration\Http;

use App\Tests\Integration\DatabaseTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class OffersMultiTenantIsolationTest extends DatabaseTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        static::ensureKernelShutdown();

        $this->resetTestDatabaseFile();

        $this->client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        $this->createSchema($em);
    }

    public function testOffersAreIsolatedPerTenant(): void
    {
        $tenantA = '01HZZZZZZZZZZZZZZZZZZZZZZZ';
        $tenantB = '01J00000000000000000000000';

        $this->client->request('POST', '/api/offers', [], [], [
            'HTTP_X_TENANT' => $tenantA,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['title' => 'Offer A1']));

        self::assertSame(201, $this->client->getResponse()->getStatusCode());

        $this->client->request('POST', '/api/offers', [], [], [
            'HTTP_X_TENANT' => $tenantB,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['title' => 'Offer B1']));

        self::assertSame(201, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/api/offers', [], [], [
            'HTTP_X_TENANT' => $tenantA,
        ]);

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $dataA = json_decode((string) $this->client->getResponse()->getContent(), true);

        self::assertCount(1, $dataA['items']);
        self::assertSame('Offer A1', $dataA['items'][0]['title']);

        $this->client->request('GET', '/api/offers', [], [], [
            'HTTP_X_TENANT' => $tenantB,
        ]);

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $dataB = json_decode((string) $this->client->getResponse()->getContent(), true);

        self::assertCount(1, $dataB['items']);
        self::assertSame('Offer B1', $dataB['items'][0]['title']);
    }
}
