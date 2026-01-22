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
        // Si déjà connecté, on ne rejoue pas de logique custom :
        // l’utilisateur n’a rien à faire sur /connexion.
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $redirect = (string) $request->query->get('redirect', '');
        if (!$this->isSafeRelativePath($redirect)) {
            $redirect = '';
        }

        return $this->render('connexion/connexion.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error'         => $authenticationUtils->getLastAuthenticationError(),
            'redirect'      => $redirect, // ✅ indispensable pour propager vers inscription + action du form
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
