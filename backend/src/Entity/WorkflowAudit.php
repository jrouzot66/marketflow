<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'workflow_audit')]
#[ORM\Index(columns: ['tenant_id'], name: 'idx_workflow_audit_tenant')]
#[ORM\Index(columns: ['subject_type', 'subject_id'], name: 'idx_workflow_audit_subject')]
final class WorkflowAudit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'tenant_id', type: 'string', length: 36)]
    private string $tenantId;

    #[ORM\Column(name: 'subject_type', type: 'string', length: 32)]
    private string $subjectType;

    #[ORM\Column(name: 'subject_id', type: 'string', length: 64)]
    private string $subjectId;

    #[ORM\Column(type: 'string', length: 64)]
    private string $transition;

    #[ORM\Column(name: 'from_place', type: 'string', length: 32, nullable: true)]
    private ?string $fromPlace;

    #[ORM\Column(name: 'to_place', type: 'string', length: 32)]
    private string $toPlace;

    #[ORM\Column(name: 'occurred_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $occurredAt;

    #[ORM\Column(name: 'actor', type: 'string', length: 128, nullable: true)]
    private ?string $actor;

    public function __construct(
        string $tenantId,
        string $subjectType,
        string $subjectId,
        string $transition,
        ?string $fromPlace,
        string $toPlace,
        ?string $actor = null
    ) {
        $this->tenantId = $tenantId;
        $this->subjectType = $subjectType;
        $this->subjectId = $subjectId;
        $this->transition = $transition;
        $this->fromPlace = $fromPlace;
        $this->toPlace = $toPlace;
        $this->occurredAt = new \DateTimeImmutable();
        $this->actor = $actor;
    }

    public function id(): ?int
    {
        return $this->id;
    }
}
