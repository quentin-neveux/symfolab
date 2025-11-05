<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HistoriqueController extends AbstractController
{
    #[Route('/historique', name: 'app_historique')]
    public function index(): Response
    {
        // Mock d’historique de trajets
        $historiques = [
            [
                'villeDepart' => 'Lyon',
                'villeArrivee' => 'Marseille',
                'dateDepart' => new \DateTime('2025-10-10'),
                'prix' => 18,
            ],
            [
                'villeDepart' => 'Bordeaux',
                'villeArrivee' => 'Toulouse',
                'dateDepart' => new \DateTime('2025-09-22'),
                'prix' => 20,
            ],
        ];

        return $this->render('historique/historique.html.twig', [
            'historiques' => $historiques,
        ]);
    }
}
