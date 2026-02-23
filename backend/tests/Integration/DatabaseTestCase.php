<?php

namespace App\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class DatabaseTestCase extends WebTestCase
{
    protected function resetTestDatabaseFile(): void
    {
        $projectDir = dirname(__DIR__, 2);
        $dbPath = $projectDir . '/var/test.db';

        if (file_exists($dbPath)) {
            @unlink($dbPath);
        }
    }

    protected function createSchema(EntityManagerInterface $em): void
    {
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        if (count($metadata) === 0) {
            throw new \RuntimeException('No Doctrine metadata found. Check mappings configuration.');
        }

        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema($metadata);
    }
}
