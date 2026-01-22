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

        $redirect = (string) $request->query->get('redirect', '');
        if (!$this->isSafeRelativePath($redirect)) {
            $redirect = '';
        }

        $user = new User();

        $form = $this->createForm(UserRegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // 🔐 HASH DU MOT DE PASSE
            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            // 💾 SAUVEGARDE EN BASE
            $em->persist($user);
            $em->flush();

            // 📧 MAIL DE BIENVENUE
            try {
                $mailer->sendInscriptionConfirmation($user);
            } catch (\Throwable $e) {
                // silencieux volontairement
            }

            $this->addFlash('success', 'Compte créé avec succès. Tu peux maintenant te connecter.');

            // ✅ on renvoie vers login en conservant redirect
            if ($redirect !== '') {
                return $this->redirectToRoute('app_connexion', [
                    'redirect' => $redirect
                ]);
            }

            return $this->redirectToRoute('app_connexion');
        }

        return $this->render('inscription/inscription.html.twig', [
            'registrationForm' => $form->createView(),
            'redirect' => $redirect, // ✅ pour construire les liens / action si besoin
        ]);
    }

    /**
     * Sécurité: on accepte uniquement un chemin relatif interne ("/...") pour éviter open redirect.
     */
    private function isSafeRelativePath(string $path): bool
    {
        if ($path === '' || $path[0] !== '/') {
            return false;
        }
        if (str_starts_with($path, '//')) {
            return false;
        }
        if (preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*:#', $path)) {
            return false;
        }
        return true;
    }
}
