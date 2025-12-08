<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileEditType;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

final class ProfilController extends AbstractController
{
    #[Route('/profil', name: 'app_profil')]
    public function index(ReviewRepository $reviewRepo): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();
        $average = $reviewRepo->getAverageRatingForUser($user->getId());

        return $this->render('profil/profil.html.twig', [
            'user' => $user,
            'averageRating' => $average,
        ]);
    }

    #[Route('/profil/compte', name: 'app_profil_compte')]
    public function compte(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        return $this->render('profil/profil_compte.html.twig');
    }

    #[Route('/profil/edit', name: 'app_profil_edit')]
    public function edit(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        ReviewRepository $reviewRepo
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ProfileEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // ============================
            //        UPLOAD PHOTO
            // ============================
            $file = $form->get('photo')->getData();

            if ($file) {
                $uploadsDir = $this->getParameter('photos_directory');

                // Supprimer l’ancienne photo
                if ($user->getPhoto()) {
                    $oldFile = $uploadsDir . '/' . $user->getPhoto();
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }

                // Nouveau nom
                $newFilename = $slugger->slug($user->getPrenom() . '-' . time())
                    . '.' . $file->guessExtension();

                // Déplacement vers dossier uploads
                try {
                    $file->move($uploadsDir, $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', "Erreur durant l'upload de la photo.");
                }

                // Set photo dans l'entité
                $user->setPhoto($newFilename);
            }

            // Persist
            $em->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès ✔️');
            return $this->redirectToRoute('app_profil');
        }

        return $this->render('profil/profil_edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}

