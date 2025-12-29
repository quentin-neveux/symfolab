<?php

namespace App\Controller;

use App\Entity\Review;
use App\Entity\Trajet;
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
        $reservation = $em->getRepository(\App\Entity\TrajetPassager::class)
            ->findOneBy([
                'trajet' => $trajet,
                'passager' => $user
            ]);

        if (!$reservation) {
            $this->addFlash('danger', "Tu ne peux pas noter un trajet auquel tu n'as pas participé.");
            return $this->redirectToRoute('app_trajet_detail', ['id' => $trajet->getId()]);
        }

        // 2️⃣ Le trajet doit être fini
        if (!$trajet->isFinished()) {
            $this->addFlash('warning', "Tu pourras laisser un avis une fois le trajet terminé.");
            return $this->redirectToRoute('app_trajet_detail', ['id' => $trajet->getId()]);
        }

        // 3️⃣ Empêcher de noter deux fois
        $existingReview = $em->getRepository(Review::class)->findOneBy([
            'author' => $user,
            'trajet' => $trajet
        ]);

        if ($existingReview) {
            $this->addFlash('info', "Tu as déjà laissé un avis pour ce trajet.");
            return $this->redirectToRoute('app_trajet_detail', ['id' => $trajet->getId()]);
        }

        // 4️⃣ Création du Review
        $review = new Review();
        $review->setAuthor($user);
        $review->setTarget($trajet->getConducteur());
        $review->setTrajet($trajet);

        // Formulaire
        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($review);
            $em->flush();

            $this->addFlash('success', "Merci ! Ton avis a été enregistré 🙌");

            return $this->redirectToRoute('app_trajet_detail', [
                'id' => $trajet->getId()
            ]);
        }
        $tags = $request->request->get('review_tags');

        if ($tags) {
            $review->setComment(trim($review->getComment() . "\n\nPoints positifs : " . $tags));
        }


        return $this->render('review/new.html.twig', [
            'trajet' => $trajet,
            'form'   => $form->createView(),
        ]);
    }
}
