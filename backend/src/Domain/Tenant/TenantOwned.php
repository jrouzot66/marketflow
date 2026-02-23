<?php

namespace App\Domain\Tenant;

/**
 * Marker interface: any persisted model that belongs to a tenant must implement this.
 * Convention: DB column name is "tenant_id".
 */
interface TenantOwned
{
    // Marker only
}
