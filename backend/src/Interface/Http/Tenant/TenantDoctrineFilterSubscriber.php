<?php

namespace App\Interface\Http\Tenant;

use App\Application\Tenant\TenantContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class TenantDoctrineFilterSubscriber implements EventSubscriberInterface
{
    private TenantContext $tenantContext;
    private EntityManagerInterface $entityManager;

    public function __construct(TenantContext $tenantContext, EntityManagerInterface $entityManager)
    {
        $this->tenantContext = $tenantContext;
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 50],
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

        $tenantId = $this->tenantContext->getTenantId();

        $filters = $this->entityManager->getFilters();
        if (!$filters->isEnabled('tenant')) {
            $filters->enable('tenant');
        }

        // Postgres uuid column => RFC4122
        $filters->getFilter('tenant')->setParameter('tenant_id', $tenantId->toRfc4122());
    }

    private function isApiRequest(Request $request): bool
    {
        return strncmp($request->getPathInfo(), '/api', 4) === 0;
    }
}
