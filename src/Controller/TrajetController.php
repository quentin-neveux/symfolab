<?php

namespace App\Controller;

use App\Entity\Trajet;
use App\Form\TrajetType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TrajetController extends AbstractController
{
    #[Route('/profil/proposer-trajet', name: 'app_proposer_trajet')]
    public function proposer(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->getUser()) {
            $request->getSession()->set('redirect_after_login', $request->getUri());
            return $this->redirectToRoute('app_connexion');
        }

        $trajet = new Trajet();
        $form = $this->createForm(TrajetType::class, $trajet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $trajet->setConducteur($this->getUser());
            $em->persist($trajet);
            $em->flush();

            $this->addFlash('success', 'Votre trajet est en ligne !');
            return $this->redirectToRoute('app_mes_trajets');
        }

        return $this->render('trajet/proposer.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profil/mes_trajets', name: 'app_mes_trajets')]
    public function mesTrajets(EntityManagerInterface $em): Response
    {
        if (!$this->getUser()) {
            $this->addFlash('warning', 'Connecte-toi pour voir tes trajets.');
            return $this->redirectToRoute('app_connexion');
        }

        $user = $this->getUser();
        $trajets = $em->getRepository(Trajet::class)
            ->findBy(['conducteur' => $user], ['dateDepart' => 'ASC']);

        return $this->render('trajet/mes_trajets.html.twig', [
            'trajets' => $trajets,
        ]);
    }

    #[Route('/trajet/{id}', name: 'app_trajet_detail')]
    public function detail(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $trajet = $em->getRepository(Trajet::class)->find($id);

        if (!$trajet) {
            throw $this->createNotFoundException('Trajet introuvable.');
        }

        if (!$this->getUser()) {
            $request->getSession()->set('redirect_after_login', $request->getUri());
            return $this->redirectToRoute('app_connexion');
        }

        return $this->render('trajet/detail.html.twig', [
            'trajet' => $trajet,
        ]);
    }
}
