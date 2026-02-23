<?php

namespace App\Entity;

use App\Domain\Tenant\TenantOwned;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'offers')]
#[ORM\Index(columns: ['tenant_id'], name: 'idx_offers_tenant')]
final class Offer implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid', unique: true)]
    private Ulid $id;

    #[ORM\Column(type: 'uuid')]
    private Uuid $tenantId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    public function __construct(Ulid $id, Uuid $tenantId, string $title)
    {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->title = $title;
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function tenantId(): Uuid
    {
        return $this->tenantId;
    }

    public function title(): string
    {
        return $this->title;
    }
}
