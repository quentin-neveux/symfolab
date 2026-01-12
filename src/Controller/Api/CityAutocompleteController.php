<?php

namespace App\Controller\Api;

use App\Repository\CityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class CityAutocompleteController extends AbstractController
{
    #[Route('/api/autocomplete/cities', name: 'api_autocomplete_cities', methods: ['GET'])]
    public function cities(Request $request, CityRepository $cityRepository): JsonResponse
    {
        $q = trim((string) $request->query->get('q', ''));

        if (mb_strlen($q) < 2) {
            return $this->json([]);
        }

        $rows = $cityRepository->suggest($q, 30);

        $out = array_map(
            fn($r) => sprintf('%s (%s)', $r['name'], $r['country']),
            $rows
        );

        return $this->json($out);
    }
}
