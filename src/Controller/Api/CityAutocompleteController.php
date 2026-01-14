<?php

namespace App\Controller\Api;

use App\Repository\CityRepository;
use Doctrine\DBAL\Exception\TableNotFoundException;
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

        try {
            $rows = $cityRepository->suggest($q, 30);
        } catch (TableNotFoundException $e) {
            // En prod si la table n'existe pas encore : on évite le 500
            return $this->json([]);
        } catch (\Throwable $e) {
            // Optionnel: éviter de casser le site pour une erreur non critique
            return $this->json([]);
        }

        $out = array_map(
            static fn ($r) => sprintf('%s (%s)', $r['name'], $r['country']),
            $rows
        );

        return $this->json($out);
    }
}
