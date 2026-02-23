<?php

namespace App\Application\Tenant;

use Symfony\Contracts\Service\ResetInterface;

final class TenantContext implements ResetInterface
{
    private ?TenantId $tenantId = null;

    public function setTenantId(TenantId $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    public function hasTenantId(): bool
    {
        return $this->tenantId !== null;
    }

    public function getTenantId(): TenantId
    {
        if ($this->tenantId === null) {
            throw new \LogicException('TenantId is not set for this request.');
        }

        return $this->tenantId;
    }

    public function reset(): void
    {
        $this->tenantId = null;
    }
}
