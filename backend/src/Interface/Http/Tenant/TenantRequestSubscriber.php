<?php

namespace App\Interface\Http\Tenant;

use App\Application\Tenant\TenantContext;
use App\Application\Tenant\TenantId;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final class TenantRequestSubscriber implements EventSubscriberInterface
{
    public const HEADER = 'X-Tenant';

    private TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 100],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!$this->isApiRequest($request)) {
            return;
        }

        $rawTenant = $request->headers->get(self::HEADER);
        if ($rawTenant === null || $rawTenant === '') {
            throw new BadRequestHttpException('Missing required header X-Tenant.');
        }

        try {
            $tenantId = TenantId::fromString((string) $rawTenant);
        } catch (\Throwable $e) {
            throw new BadRequestHttpException('Invalid X-Tenant ULID format.');
        }

        $this->tenantContext->setTenantId($tenantId);
    }

    private function isApiRequest(Request $request): bool
    {
        $path = $request->getPathInfo();

        // Tenant obligatoire sur /api/*
        if (strncmp($path, '/api', 4) !== 0) {
            return false;
        }

        // On pourra exclure /api/docs plus tard si besoin (OpenAPI)
        return true;
    }
}
