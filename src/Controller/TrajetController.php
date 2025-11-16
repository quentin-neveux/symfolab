<?php

namespace App\Controller;

use App\Entity\Trajet;
use App\Form\TrajetType;
use App\Form\TrajetEditType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TrajetController extends AbstractController
{
    // ----------------------------------------------------------
    // 🟢 Proposer un nouveau trajet
    // ----------------------------------------------------------
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

            $this->addFlash('success', '✅ Votre trajet a bien été publié.');
            return $this->redirectToRoute('app_mes_trajets');
        }

        return $this->render('trajet/proposer.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // ----------------------------------------------------------
    // 🟡 Voir ses trajets
    // ----------------------------------------------------------
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

    // 🔥 ON NE BLOQUE PAS L’ACCÈS
    // Mais si l’utilisateur n’est pas connecté,
    // on stocke la page actuelle pour le renvoyer dessus après login
    if (!$this->getUser()) {
        $request->getSession()->set('redirect_after_login', $request->getUri());
    }

    return $this->render('trajet/detail.html.twig', [
        'trajet' => $trajet,
    ]);
}



    // ----------------------------------------------------------
    // ✏️ Modifier un trajet existant
    // ----------------------------------------------------------
    #[Route('/profil/trajet/{id}/edit', name: 'app_trajet_edit')]
    public function edit(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $trajet = $em->getRepository(Trajet::class)->find($id);

        if (!$trajet) {
            throw $this->createNotFoundException('Trajet introuvable.');
        }

        // Vérifie que l'utilisateur est bien le conducteur
        $user = $this->getUser();
        if (!$user || $trajet->getConducteur() !== $user) {
            throw $this->createAccessDeniedException('Tu ne peux modifier que tes propres trajets.');
        }

        $form = $this->createForm(TrajetEditType::class, $trajet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // ✅ On conserve la date d’origine et on change uniquement l’heure
            $oldDate = $trajet->getDateDepart();
            $newTime = $form->get('dateDepart')->getData(); // instance DateTime "1970-01-01 H:i"

            if ($newTime instanceof \DateTimeInterface) {
                $oldDate->setTime(
                    (int)$newTime->format('H'),
                    (int)$newTime->format('i')
                );
                $trajet->setDateDepart($oldDate);
            }

            $em->flush();
            $this->addFlash('success', '✅ Trajet modifié avec succès.');
            return $this->redirectToRoute('app_mes_trajets');
        }

        return $this->render('trajet/edit.html.twig', [
            'trajet' => $trajet,
            'form' => $form->createView(),
        ]);
    }

        // ----------------------------------------------------------
        // ❌ Supprimer un trajet
        // ----------------------------------------------------------
        #[Route('/profil/trajet/{id}/delete', name: 'app_trajet_delete', methods: ['GET'])]
        public function delete(int $id, EntityManagerInterface $em): Response
        {
            $trajet = $em->getRepository(Trajet::class)->find($id);
        
            if (!$trajet) {
                throw $this->createNotFoundException('Trajet introuvable.');
            }
        
            // Vérifie que c’est bien le conducteur connecté
            $user = $this->getUser();
            if (!$user || $trajet->getConducteur() !== $user) {
                throw $this->createAccessDeniedException('Tu ne peux supprimer que tes propres trajets.');
            }
        
            $em->remove($trajet);
            $em->flush();
        
            $this->addFlash('success', '🗑️ Ton trajet a bien été supprimé.');
        
            // Redirige vers le profil (ou la liste des trajets)
            return $this->redirectToRoute('app_mes_trajets');
        }
        }