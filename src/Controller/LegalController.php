<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class LegalController extends AbstractController
{
    #[Route('/mentions-legales', name: 'app_mentions_legales')]
    public function mentions(): Response
    {
        return $this->render('pages/mentions_legales.html.twig');
    }

    #[Route('/confidentialite', name: 'app_confidentialite')]
    public function confidentialite(): Response
    {
        return $this->render('pages/confidentialite.html.twig');
    }
}
