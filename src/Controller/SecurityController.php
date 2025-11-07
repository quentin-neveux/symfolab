<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class SecurityController extends AbstractController
{
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): Response
    {
        // Symfony gère ça automatiquement, ne pas supprimer cette exception
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
