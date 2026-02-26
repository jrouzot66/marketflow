<?php

namespace App\Controller\Api;

use App\Application\Offer\CreateOffer;
use App\Application\Offer\GetOfferDetails;
use App\Application\Offer\ListOffers;
use App\Application\Offer\SetOfferTags;
use App\Interface\Http\Api\Dto\CreateOfferRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class OfferController extends AbstractController
{
    #[Route('/api/offers', name: 'api_offer_create', methods: ['POST'])]
    public function create(
        Request $request,
        CreateOffer $createOffer,
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

        $offer = $createOffer->create($dto->title);

        return $this->json([
            'id' => $offer->id,
            'tenantId' => $offer->tenantId,
            'title' => $offer->title,
        ], 201);
    }

    #[Route('/api/offers', name: 'api_offer_list', methods: ['GET'])]
    public function list(ListOffers $listOffers): JsonResponse
    {
        $items = [];
        foreach ($listOffers->list() as $offer) {
            $items[] = [
                'id' => $offer->id,
                'tenantId' => $offer->tenantId,
                'title' => $offer->title,
            ];
        }

        return $this->json(['items' => $items]);
    }

    #[Route('/api/offers/{id}', name: 'api_offer_get', methods: ['GET'])]
    public function get(string $id, GetOfferDetails $getOfferDetails): JsonResponse
    {
        return $this->json($getOfferDetails->get($id));
    }

    #[Route('/api/offers/{id}/tags', name: 'api_offer_set_tags', methods: ['POST'])]
    public function setTags(string $id, Request $request, SetOfferTags $setOfferTags): JsonResponse
    {
        $payload = json_decode((string) $request->getContent(), true);

        if (!is_array($payload) || !isset($payload['tags'])) {
            return $this->json(['error' => 'Missing "tags" field'], 400);
        }

        $tags = $setOfferTags->setFromRawInput($id, (string) $payload['tags']);

        return $this->json(['tags' => $tags]);
    }
}
