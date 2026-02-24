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

    #[ORM\Column(type: 'string', length: 32)]
    private string $status;

    public function __construct(Ulid $id, string $tenantId, string $title, string $status = 'draft')
    {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->title = $title;
        $this->status = $status;
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

    public function status(): string
    {
        return $this->status;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }
}
