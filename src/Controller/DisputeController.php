<?php

namespace App\Controller;

use App\Entity\Dispute;
use App\Entity\Trajet;
use App\Entity\TrajetPassager;
use App\Repository\DisputeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DisputeController extends AbstractController
{
    #[Route('/trajet/{id}/signaler', name: 'trajet_signaler', requirements: ['id' => '\d+'])]
    public function signaler(
        Trajet $trajet,
        Request $request,
        EntityManagerInterface $em,
        DisputeRepository $disputeRepo
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $reservation = $em->getRepository(TrajetPassager::class)->findOneBy([
            'trajet' => $trajet,
            'passager' => $user,
        ]);

        $isConducteur = $trajet->getConducteur()?->getId() === $user->getId();

        // ✅ Sécurité : passager du trajet OU conducteur
        if (!$reservation && !$isConducteur) {
            $this->addFlash('danger', "Accès refusé.");
            return $this->redirectToRoute('app_trajet_detail', ['id' => $trajet->getId()]);
        }

        // ✅ Côté passager : signalement uniquement quand le trajet est terminable/notable
        if (!$isConducteur && !$reservation?->peutNoter()) {
            $this->addFlash('warning', "Le trajet n'est pas terminé.");
            return $this->redirectToRoute('app_trajet_detail', ['id' => $trajet->getId()]);
        }

        // ✅ Anti-spam 1 : déjà un signalement actif (OPEN/IN_REVIEW)
        $existingActive = $disputeRepo->findActiveForReporterAndTrajet($user->getId(), $trajet->getId());
        if ($existingActive) {
            $this->addFlash('info', 'Tu as déjà un signalement en cours pour ce trajet.');
            return $this->redirectToRoute('app_trajet_detail', ['id' => $trajet->getId()]);
        }

        // ✅ Anti-spam 2 : cooldown 24h (même si résolu/rejeté)
        $since = new \DateTimeImmutable('-24 hours');
        if ($disputeRepo->hasRecentForReporterAndTrajet($user->getId(), $trajet->getId(), $since)) {
            $this->addFlash('warning', 'Tu as déjà signalé ce trajet récemment. Réessaie plus tard.');
            return $this->redirectToRoute('app_trajet_detail', ['id' => $trajet->getId()]);
        }

        $target = $trajet->getConducteur();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('dispute_' . $trajet->getId(), (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException();
            }

            $reason  = (string) $request->request->get('reason', '');
            $message = (string) $request->request->get('message', '');

            if (trim($reason) === '') {
                $this->addFlash('danger', 'Choisis une raison.');
                return $this->redirectToRoute('trajet_signaler', ['id' => $trajet->getId()]);
            }

            // ✅ Tokens payés par le reporter (figés au moment du signalement)
            // Passager: tokenCost + 2 (frais plateforme). Conducteur: 0.
            $reporterTokensPaid = 0;
            if (!$isConducteur) {
                $reporterTokensPaid = (int) $trajet->getTokenCost() + 2;
            }

            $dispute = new Dispute();
            $dispute
                ->setTrajet($trajet)
                ->setReporter($user)
                ->setTarget($target)
                ->setReason($reason)
                ->setMessage($message)
                ->setReporterTokensPaid($reporterTokensPaid);

            $em->persist($dispute);
            $em->flush();

            $this->addFlash('success', 'Signalement envoyé.');
            return $this->redirectToRoute('app_trajet_detail', ['id' => $trajet->getId()]);
        }
dump(__FILE__);
dump('TEMPLATE: admin/dispute/show.html.twig'); // ou le vrai path

        return $this->render('dispute/new.html.twig', [
            'trajet' => $trajet,
            'target' => $target,
        ]);
    }
}
