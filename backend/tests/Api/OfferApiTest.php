<?php
// backend/tests/Api/OfferApiTest.php

namespace App\Tests\Api;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Ulid;

final class OfferApiTest extends WebTestCase
{
    private ?EntityManagerInterface $em = null;
    private ?KernelBrowser $client = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();

        if ($metadata !== []) {
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        }
    }

    public function testTenantHeaderIsRequiredOnOffersList(): void
    {
        $this->client->request('GET', '/api/offers');

        self::assertResponseStatusCodeSame(400);
    }

    public function testCrossTenantIsolationOnOffers(): void
    {
        $tenantA = (string) new Ulid();
        $tenantB = (string) new Ulid();

        // Create A1
        $this->client->request(
            'POST',
            '/api/offers',
            server: [
                'HTTP_X_TENANT' => $tenantA,
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode(['title' => 'Offer A1'], JSON_THROW_ON_ERROR)
        );
        self::assertResponseStatusCodeSame(201);

        // Create B1
        $this->client->request(
            'POST',
            '/api/offers',
            server: [
                'HTTP_X_TENANT' => $tenantB,
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode(['title' => 'Offer B1'], JSON_THROW_ON_ERROR)
        );
        self::assertResponseStatusCodeSame(201);

        // List A => only A1
        $this->client->request('GET', '/api/offers', server: ['HTTP_X_TENANT' => $tenantA]);
        self::assertResponseIsSuccessful();

        $payloadA = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payloadA);
        self::assertArrayHasKey('items', $payloadA);
        self::assertCount(1, $payloadA['items']);
        self::assertSame('Offer A1', $payloadA['items'][0]['title']);

        // List B => only B1
        $this->client->request('GET', '/api/offers', server: ['HTTP_X_TENANT' => $tenantB]);
        self::assertResponseIsSuccessful();

        $payloadB = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payloadB);
        self::assertArrayHasKey('items', $payloadB);
        self::assertCount(1, $payloadB['items']);
        self::assertSame('Offer B1', $payloadB['items'][0]['title']);
    }
}
