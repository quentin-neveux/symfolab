<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileEditType;
use App\Repository\ReviewRepository;
use App\Repository\VehicleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

final class ProfilController extends AbstractController
{
    // =========================================================
    // PAGE PROFIL (vue principale)
    // =========================================================
    #[Route('/profil', name: 'app_profil')]
    public function index(
        ReviewRepository $reviewRepo,
        VehicleRepository $vehicleRepo
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        // Note moyenne
        $average = $reviewRepo->getAverageRatingForUser($user->getId());

        // Véhicules du user
        $vehicles = $vehicleRepo->findByUser($user);

        // ⭐ Avis reçus (SAFE) : on filtre ceux dont le trajet existe vraiment
        // (évite EntityNotFoundException si un trajet a été supprimé)
        $reviews = $reviewRepo->createQueryBuilder('r')
            ->innerJoin('r.trajet', 't')     // ✅ si le trajet n’existe plus -> l’avis n’est pas renvoyé
            ->addSelect('t')                // ✅ évite du lazy-loading surprise dans Twig
            ->where('r.target = :u')
            ->setParameter('u', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('profil/profil.html.twig', [
            'user'          => $user,
            'averageRating' => $average,
            'vehicles'      => $vehicles,
            'reviews'       => $reviews,
        ]);
    }

    // =========================================================
    // PROFIL PUBLIC D'UN UTILISATEUR
    // =========================================================
    #[Route(
        '/profil/utilisateur/{id}',
        name: 'app_profil_public',
        requirements: ['id' => '\d+']
    )]
    public function publicProfile(
        User $user,
        ReviewRepository $reviewRepo,
        VehicleRepository $vehicleRepo
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $average  = $reviewRepo->getAverageRatingForUser($user->getId());
        $vehicles = $vehicleRepo->findByUser($user);

        // Avis reçus (SAFE)
        $reviews = $reviewRepo->createQueryBuilder('r')
            ->innerJoin('r.trajet', 't')
            ->addSelect('t')
            ->where('r.target = :u')
            ->setParameter('u', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('profil/details_informations.html.twig', [
            'user'          => $user,
            'averageRating' => $average,
            'vehicles'      => $vehicles,
            'reviews'       => $reviews,
        ]);
    }

    // =========================================================
    // PAGE D’INFOS DU COMPTE
    // =========================================================
    #[Route('/profil/compte', name: 'app_profil_compte')]
    public function compte(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->render('profil/profil_compte.html.twig');
    }

    // =========================================================
    // EDITION DU PROFIL
    // =========================================================
    #[Route('/profil/edit', name: 'app_profil_edit')]
    public function edit(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ProfileEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // 🔼 UPLOAD PHOTO
            $file = $form->get('photo')->getData();

            if ($file) {
                $uploadsDir = $this->getParameter('photos_directory');

                if ($user->getPhoto()) {
                    $old = $uploadsDir . '/' . $user->getPhoto();
                    if (file_exists($old)) {
                        unlink($old);
                    }
                }

                $newFilename =
                    $slugger->slug($user->getPrenom() . '-' . time()) .
                    '.' .
                    $file->guessExtension();

                try {
                    $file->move($uploadsDir, $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', "Erreur durant l'upload.");
                }

                $user->setPhoto($newFilename);
            }

            $em->flush();

            $this->addFlash('success', 'Profil mis à jour ✔️');
            return $this->redirectToRoute('app_profil');
        }

        return $this->render('profil/profil_edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
