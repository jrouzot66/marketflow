<?php

namespace App\Infrastructure\Http;

use App\Application\Tenant\TenantContext;
use App\Application\Tenant\TenantId;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Résout le tenant depuis le header X-Tenant et le met dans TenantContext
 * Normes DDD : middleware pour isolement multi-tenant
 */
final class TenantResolver implements EventSubscriberInterface
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 512],  // Priority haute pour exécution tôt
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Résoudre le tenant depuis le header
        $tenantIdString = $request->headers->get('X-Tenant');

        if ($tenantIdString === null) {
            $this->logger->warning('X-Tenant header missing', ['path' => $request->getPathInfo()]);
            return;
        }

        try {
            $tenantId = TenantId::fromString($tenantIdString);
            $this->tenantContext->setTenantId($tenantId);

            $this->logger->debug('Tenant resolved', ['tenantId' => (string) $tenantId]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to resolve tenant', [
                'tenantIdString' => $tenantIdString,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
