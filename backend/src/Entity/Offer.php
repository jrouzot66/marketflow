<?php

namespace App\Entity;

use App\Domain\Tenant\TenantOwned;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'offers')]
#[ORM\Index(columns: ['tenant_id'], name: 'idx_offers_tenant')]
final class Offer implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid', unique: true)]
    private Ulid $id;

    #[ORM\Column(name: 'tenant_id', type: 'string', length: 36)]
    private string $tenantId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    public function __construct(Ulid $id, string $tenantId, string $title)
    {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->title = $title;
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function tenantId(): string
    {
        return $this->tenantId;
    }

    public function title(): string
    {
        return $this->title;
    }
}
