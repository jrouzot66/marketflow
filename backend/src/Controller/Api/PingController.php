<?php

namespace App\Controller\Api;

use App\Application\Tenant\TenantContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class PingController extends AbstractController
{
    #[Route('/api/ping', name: 'api_ping', methods: ['GET'])]
    public function __invoke(TenantContext $tenantContext): JsonResponse
    {
        return $this->json([
            'ok' => true,
            'tenant' => (string) $tenantContext->getTenantId(),
        ]);
    }
}
