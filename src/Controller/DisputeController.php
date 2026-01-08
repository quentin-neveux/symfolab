<?php

namespace App\Controller;

use App\Entity\Dispute;
use App\Entity\Review;
use App\Entity\Trajet;
use App\Entity\TrajetPassager;
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
        EntityManagerInterface $em
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // ✅ Sécurité : il faut avoir participé au trajet (passager) OU être conducteur
        $reservation = $em->getRepository(TrajetPassager::class)->findOneBy([
            'trajet' => $trajet,
            'passager' => $user,
        ]);

        $isConducteur = ($trajet->getConducteur() && $trajet->getConducteur()->getId() === $user->getId());

        if (!$reservation && !$isConducteur) {
            $this->addFlash('danger', "Tu ne peux signaler que les trajets auxquels tu as participé.");
            return $this->redirectToRoute('app_trajet_detail', ['id' => $trajet->getId()]);
        }

        // Target par défaut : conducteur (logique “signaler utilisateur/trajet” au moment de l’avis)
        $target = $trajet->getConducteur();

        // Si tu veux plus tard permettre de choisir “trajet” ou “utilisateur”, on étendra.
        $reason = (string) $request->request->get('reason', '');
        $message = (string) $request->request->get('message', '');

        // Lien optionnel vers l'avis si déjà créé (ou si tu veux lier après coup)
        $review = $em->getRepository(Review::class)->findOneBy([
            'author' => $user,
            'trajet' => $trajet,
        ]);

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('dispute_' . $trajet->getId(), (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException();
            }

            if (trim($reason) === '') {
                $this->addFlash('danger', 'Choisis une raison.');
                return $this->redirectToRoute('trajet_signaler', ['id' => $trajet->getId()]);
            }

            $dispute = new Dispute();
            $dispute->setTrajet($trajet);
            $dispute->setReporter($user);
            $dispute->setTarget($target);
            $dispute->setReason($reason);
            $dispute->setMessage(trim($message) !== '' ? $message : null);
            $dispute->setReview($review);

            $em->persist($dispute);
            $em->flush();

            $this->addFlash('success', 'Signalement envoyé. Un employé EcoRide va traiter la demande.');
            return $this->redirectToRoute('app_trajet_detail', ['id' => $trajet->getId()]);
        }

        return $this->render('dispute/new.html.twig', [
            'trajet' => $trajet,
            'target' => $target,
        ]);
    }
}
