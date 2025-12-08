<?php

namespace App\Controller;

use App\Entity\Review;
use App\Entity\User;
use App\Form\ReviewType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReviewController extends AbstractController
{
    #[Route('/review/{id}', name: 'app_review')]
    public function review(
        User $target, 
        Request $request, 
        EntityManagerInterface $em
    ): Response {

        $author = $this->getUser();
        if (!$author) {
            return $this->redirectToRoute('app_login');
        }

        if ($author === $target) {
            $this->addFlash('danger', 'Tu ne peux pas te noter toi-même.');
            return $this->redirectToRoute('app_profil');
        }

        $review = new Review();
        $review->setAuthor($author);
        $review->setTarget($target);

        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($review);
            $em->flush();

            $this->addFlash('success', 'Avis envoyé, merci !');
            return $this->redirectToRoute('app_profil');
        }

        return $this->render('review/review.html.twig', [
            'form' => $form->createView(),
            'target' => $target
        ]);
    }
}
