<?php

namespace App\Infrastructure\Search;

use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Client;

/**
 * Factory pour créer le client Elasticsearch
 * Normes : isoler la création du client
 */
final class ElasticsearchClientFactory
{
    public static function create(): Client
    {
        return ClientBuilder::create()
            ->setHosts(['http://elasticsearch:9200'])
            ->build();
    }
}
