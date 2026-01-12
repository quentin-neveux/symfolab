<?php

namespace App\Controller\Api;

use App\Repository\TrajetRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class CityAutocompleteController extends AbstractController
{
    #[Route('/api/autocomplete/cities', name: 'api_autocomplete_cities', methods: ['GET'])]
    public function cities(Request $request, TrajetRepository $trajetRepository): JsonResponse
    {
        $q = trim((string) $request->query->get('q', ''));

        if (mb_strlen($q) < 2) {
            return $this->json([]);
        }

        // Cherche dans villeDepart + villeArrivee (strings) et renvoie une liste unique.
        $cities = $trajetRepository->findCitySuggestions($q, 10);

        return $this->json($cities);
    }
}
