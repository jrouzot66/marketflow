<?php

namespace App\Controller\Api;

use App\Application\Offer\ApplyOfferTransition;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class OfferWorkflowController extends AbstractController
{
    #[Route('/api/offers/{id}/submit', name: 'api_offer_submit', methods: ['POST'])]
    public function submit(string $id, ApplyOfferTransition $apply): JsonResponse
    {
        $offer = $apply->apply($id, 'submit');

        return $this->json([
            'id' => (string) $offer->id(),
            'status' => $offer->status(),
        ]);
    }

    #[Route('/api/offers/{id}/approve', name: 'api_offer_approve', methods: ['POST'])]
    public function approve(string $id, ApplyOfferTransition $apply): JsonResponse
    {
        $offer = $apply->apply($id, 'approve');

        return $this->json([
            'id' => (string) $offer->id(),
            'status' => $offer->status(),
        ]);
    }

    #[Route('/api/offers/{id}/publish', name: 'api_offer_publish', methods: ['POST'])]
    public function publish(string $id, ApplyOfferTransition $apply): JsonResponse
    {
        $offer = $apply->apply($id, 'publish');

        return $this->json([
            'id' => (string) $offer->id(),
            'status' => $offer->status(),
        ]);
    }

    #[Route('/api/offers/{id}/reject', name: 'api_offer_reject', methods: ['POST'])]
    public function reject(string $id, ApplyOfferTransition $apply): JsonResponse
    {
        $offer = $apply->apply($id, 'reject');

        return $this->json([
            'id' => (string) $offer->id(),
            'status' => $offer->status(),
        ]);
    }

    #[Route('/api/offers/{id}/archive', name: 'api_offer_archive', methods: ['POST'])]
    public function archive(string $id, ApplyOfferTransition $apply): JsonResponse
    {
        $offer = $apply->apply($id, 'archive');

        return $this->json([
            'id' => (string) $offer->id(),
            'status' => $offer->status(),
        ]);
    }
}
