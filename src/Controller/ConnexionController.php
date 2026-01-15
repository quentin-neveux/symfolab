<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class ConnexionController extends AbstractController
{
    #[Route('/connexion', name: 'app_connexion')]
    public function index(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        // Vérifie si l'utilisateur est déjà connecté
        if ($this->getUser()) {
            $session = $request->getSession();

            // 🔁 Redirection après login si une URL est enregistrée
            if ($session->has('redirect_after_login')) {
                $url = $session->get('redirect_after_login');
                $session->remove('redirect_after_login');
                return $this->redirect($url);
            }

            // Sinon, redirection par défaut vers le home
            return $this->redirectToRoute('app_home');
        }

        // Récupère la dernière erreur et le dernier identifiant saisi
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('connexion/connexion.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }
}
