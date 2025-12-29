<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserRegistrationFormType;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    #[Route('/inscription', name: 'app_inscription')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        MailerService $mailer
    ): Response {

        $user = new User();

        $form = $this->createForm(UserRegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // --------------------------------------------------
            // 🔐 HASH DU MOT DE PASSE
            // --------------------------------------------------
            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $plainPassword
            );
            $user->setPassword($hashedPassword);

            // --------------------------------------------------
            // 💾 SAUVEGARDE EN BASE
            // --------------------------------------------------
            $em->persist($user);
            $em->flush();

            // --------------------------------------------------
            // 📧 MAIL DE BIENVENUE
            // --------------------------------------------------
            try {
                $mailer->sendInscriptionConfirmation($user);
            } catch (\Throwable $e) {
                // volontairement silencieux
                // (l’inscription ne doit pas échouer à cause du mail)
            }

            // --------------------------------------------------
            // 🔔 MESSAGE + REDIRECTION
            // --------------------------------------------------
            $this->addFlash(
                'success',
                'Compte créé avec succès. Tu peux maintenant te connecter.'
            );

            return $this->redirectToRoute('app_connexion');
        }

        // --------------------------------------------------
        // 📄 FORMULAIRE
        // --------------------------------------------------
        return $this->render('inscription/inscription.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
