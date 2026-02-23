<?php

namespace App\Tests\Integration\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TenantHeaderTest extends WebTestCase
{
    public function testPingRequiresTenantHeader(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/ping');

        self::assertSame(400, $client->getResponse()->getStatusCode());
    }

    public function testPingAcceptsValidTenantHeader(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/ping', [], [], [
            'HTTP_X_TENANT' => '01HZZZZZZZZZZZZZZZZZZZZZZZ',
        ]);

        self::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
