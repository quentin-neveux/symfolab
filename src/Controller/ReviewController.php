<?php

namespace App\Controller;

use App\Entity\Review;
use App\Entity\Trajet;
use App\Entity\TrajetPassager;
use App\Form\ReviewType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReviewController extends AbstractController
{
    #[Route('/trajet/{id}/review', name: 'app_review_new', requirements: ['id' => '\d+'])]
    public function new(
        Trajet $trajet,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_connexion');
        }

        // 1️⃣ Vérifier que l'utilisateur était passager du trajet
        /** @var TrajetPassager|null $reservation */
        $reservation = $em->getRepository(TrajetPassager::class)
            ->findOneBy([
                'trajet'   => $trajet,
                'passager' => $user,
            ]);

        if (!$reservation) {
            $this->addFlash('danger', "Tu ne peux pas noter un trajet auquel tu n'as pas participé.");
            return $this->redirectToRoute('app_trajet_detail', ['id' => $trajet->getId()]);
        }

        // 2️⃣ Vérifier les conditions métier (paiement + fins confirmées + pas encore noté)
        if (!$reservation->peutNoter()) {
            $this->addFlash('warning', "Tu pourras laisser un avis une fois le trajet terminé et validé.");
            return $this->redirectToRoute('app_trajet_detail', ['id' => $trajet->getId()]);
        }

        // 3️⃣ Empêcher de noter deux fois (par sécurité)
        $existingReview = $em->getRepository(Review::class)->findOneBy([
            'author' => $user,
            'trajet' => $trajet,
        ]);

        if ($existingReview) {
            $this->addFlash('info', "Tu as déjà laissé un avis pour ce trajet.");
            return $this->redirectToRoute('app_trajet_detail', ['id' => $trajet->getId()]);
        }

        // 4️⃣ Création du Review (createdAt est géré dans le __construct)
        $review = new Review();
        $review->setAuthor($user);
        $review->setTarget($trajet->getConducteur());
        $review->setTrajet($trajet);

        // Formulaire
        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // 🎯 Récupérer les tags cliquables (input hidden "review_tags")
            $tags = $request->request->get('review_tags');
            if ($tags) {
                $comment = $review->getComment() ?: '';
                if ($comment !== '') {
                    $comment .= "\n\n";
                }
                $comment .= 'Points positifs : ' . $tags;
                $review->setComment($comment);
            }

            $em->persist($review);

            // Marquer la réservation comme déjà notée
            $reservation->setADejaNote(true);

            $em->flush();

            $this->addFlash('success', "Merci ! Ton avis a été enregistré.");

            return $this->redirectToRoute('app_trajet_detail', [
                'id' => $trajet->getId(),
            ]);
        }

        return $this->render('review/new.html.twig', [
            'trajet' => $trajet,
            'form'   => $form->createView(),
        ]);
    }
}
