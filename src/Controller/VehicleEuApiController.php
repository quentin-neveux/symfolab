<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class VehicleEuApiController extends AbstractController
{
    private const DATA_FILE = 'data/vehicles_eu.json';

    public function __construct(
        private CacheInterface $cache,
        private KernelInterface $kernel
    ) {}

    #[Route('/api/vehicles-eu/makes', name: 'api_vehicle_eu_makes', methods: ['GET'])]
    public function makes(): JsonResponse
    {
        $makes = $this->cache->get('vehicles_eu_makes_v1', function (ItemInterface $item) {
            $item->expiresAfter(86400);

            $data = $this->loadData();
            $makes = array_keys($data);

            sort($makes, SORT_NATURAL | SORT_FLAG_CASE);

            return $makes;
        });

        return $this->json($makes);
    }

    #[Route('/api/vehicles-eu/models/{make}', name: 'api_vehicle_eu_models', methods: ['GET'])]
    public function models(string $make): JsonResponse
    {
        $make = trim($make);
        if ($make === '') {
            return $this->json([]);
        }

        $cacheKey = 'vehicles_eu_models_' . md5(mb_strtolower($make)) . '_v1';

        $models = $this->cache->get($cacheKey, function (ItemInterface $item) use ($make) {
            $item->expiresAfter(86400);

            $data = $this->loadData();

            foreach ($data as $brand => $modelsList) {
                if (mb_strtolower($brand) === mb_strtolower($make)) {

                    usort($modelsList, function (array $a, array $b) {
                        return strcasecmp($a['name'], $b['name']);
                    });

                    return $modelsList;
                }
            }


            return [];
        });

        return $this->json($models);
    }

    private function loadData(): array
    {
        $path = $this->kernel->getProjectDir()
            . DIRECTORY_SEPARATOR
            . self::DATA_FILE;

        if (!is_file($path)) {
            throw new \RuntimeException(sprintf('Fichier introuvable : %s', $path));
        }

        $json = file_get_contents($path);
        if ($json === false) {
            throw new \RuntimeException('Impossible de lire vehicles_eu.json');
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new \RuntimeException('JSON invalide dans vehicles_eu.json');
        }

        return $data;
    }
}
