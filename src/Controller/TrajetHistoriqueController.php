<?php

namespace App\Controller;

use App\Entity\Trajet;
use App\Entity\TrajetPassager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TrajetHistoriqueController extends AbstractController
{
    #[Route('/trajet_historique', name: 'app_trajet_historique')]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('warning', 'Connecte-toi pour accéder à ton historique.');
            return $this->redirectToRoute('app_connexion');
        }

        $now = new \DateTimeImmutable();

        // Tableaux finaux
        $trajetsAvenir = [];
        $trajetsEnCours = [];
        $trajetsPasses = [];

        // ============================================================
        // 1️⃣ TRAJETS OÙ L’UTILISATEUR EST CONDUCTEUR
        // ============================================================
        $trajetsConducteur = $em->getRepository(Trajet::class)->findBy([
            'conducteur' => $user
        ]);

        foreach ($trajetsConducteur as $trajet) {
            $this->classerTrajet(
                $trajet,
                'conducteur',
                null,              // pas de TrajetPassager côté conducteur
                $now,
                $trajetsAvenir,
                $trajetsEnCours,
                $trajetsPasses
            );
        }

        // ============================================================
        // 2️⃣ TRAJETS OÙ L’UTILISATEUR EST PASSAGER
        // ============================================================
        $trajetPassagers = $em->getRepository(TrajetPassager::class)->findBy([
            'passager' => $user
        ]);

        foreach ($trajetPassagers as $tp) {
            $trajet = $tp->getTrajet();
            if (!$trajet) {
                continue;
            }

            $this->classerTrajet(
                $trajet,
                'passager',
                $tp,               // réservation liée
                $now,
                $trajetsAvenir,
                $trajetsEnCours,
                $trajetsPasses
            );
        }

        // ============================================================
        // 3️⃣ TRI DES LISTES
        //    - A venir : du plus proche au plus lointain
        //    - En cours : plus récent d'abord
        //    - Passés : plus récent d'abord
        // ============================================================
        usort($trajetsAvenir, function ($a, $b) {
            return $a['trajet']->getDateDepart() <=> $b['trajet']->getDateDepart();
        });

        usort($trajetsEnCours, function ($a, $b) {
            return $b['trajet']->getDateDepart() <=> $a['trajet']->getDateDepart();
        });

        usort($trajetsPasses, function ($a, $b) {
            return $b['trajet']->getDateDepart() <=> $a['trajet']->getDateDepart();
        });

        return $this->render('trajet/trajet_historique.html.twig', [
            'trajetsAvenir' => $trajetsAvenir,
            'trajetsEnCours' => $trajetsEnCours,
            'trajetsPasses' => $trajetsPasses,
        ]);


        }

    /**
     * Classe un trajet dans "à venir", "en cours" ou "passés"
     * en fonction de dateDepart / dateArrivee.
     *
     * - A venir : now < dateDepart
     * - En cours : dateDepart <= now < dateArrivee
     * - Passés : dateArrivee <= now
     * - Si dateArrivee est null :
     *      - now < dateDepart => à venir
     *      - sinon => passés
     */
    private function classerTrajet(
        Trajet $trajet,
        string $role,
        ?TrajetPassager $reservation,
        \DateTimeImmutable $now,
        array &$trajetsAvenir,
        array &$trajetsEnCours,
        array &$trajetsPasses
    ): void {
        $dateDepart = $trajet->getDateDepart();
        $dateArrivee = $trajet->getDateArrivee();

        if (!$dateDepart) {
            // Sécurité : si pas de dateDepart, on ignore
            return;
        }

        // Cas avec date d’arrivée
        if ($dateArrivee instanceof \DateTimeInterface) {
            if ($now < $dateDepart) {
                $trajetsAvenir[] = [
                    'trajet' => $trajet,
                    'role' => $role,
                    'reservation' => $reservation,
                ];
            } elseif ($now >= $dateDepart && $now < $dateArrivee) {
                $trajetsEnCours[] = [
                    'trajet' => $trajet,
                    'role' => $role,
                    'reservation' => $reservation,
                ];
            } else {
                $trajetsPasses[] = [
                    'trajet' => $trajet,
                    'role' => $role,
                    'reservation' => $reservation,
                ];
            }
            return;
        }

        // Cas sans date d’arrivée : on se contente de dateDepart
        if ($now < $dateDepart) {
            $trajetsAvenir[] = [
                'trajet' => $trajet,
                'role' => $role,
                'reservation' => $reservation,
            ];
        } else {
            $trajetsPasses[] = [
                'trajet' => $trajet,
                'role' => $role,
                'reservation' => $reservation,
            ];
        }
    }
}
