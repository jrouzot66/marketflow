<?php

namespace App\Controller\Api;

use App\Application\Tenant\TenantContext;
use App\Entity\Offer;
use App\Interface\Http\Api\Dto\CreateOfferRequest;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class OfferController extends AbstractController
{
    #[Route('/api/offers', name: 'api_offer_create', methods: ['POST'])]
    public function create(
        Request $request,
        TenantContext $tenantContext,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): JsonResponse {
        $payload = json_decode((string) $request->getContent(), true);

        $dto = new CreateOfferRequest();
        if (is_array($payload) && array_key_exists('title', $payload)) {
            $dto->title = (string) $payload['title'];
        }

        $violations = $validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = [
                    'path' => (string) $violation->getPropertyPath(),
                    'message' => (string) $violation->getMessage(),
                ];
            }

            return $this->json(['errors' => $errors], 422);
        }

        $tenantUuid = Uuid::fromString($tenantContext->getTenantId()->toRfc4122());

        $offer = new Offer(
            new Ulid(),
            $tenantUuid,
            $dto->title
        );

        $entityManager->persist($offer);
        $entityManager->flush();

        return $this->json([
            'id' => (string) $offer->id(),
            'tenantId' => $offer->tenantId()->toRfc4122(),
            'title' => $offer->title(),
        ], 201);
    }

    #[Route('/api/offers', name: 'api_offer_list', methods: ['GET'])]
    public function list(EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var EntityRepository<Offer> $repo */
        $repo = $entityManager->getRepository(Offer::class);

        // Doctrine filter applies automatically: only current tenant rows come back
        $offers = $repo->findBy([], ['title' => 'ASC']);

        $data = [];
        foreach ($offers as $offer) {
            $data[] = [
                'id' => (string) $offer->id(),
                'tenantId' => $offer->tenantId()->toRfc4122(),
                'title' => $offer->title(),
            ];
        }

        return $this->json(['items' => $data]);
    }
}
