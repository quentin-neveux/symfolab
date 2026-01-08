<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class LegalController extends AbstractController
{
    #[Route('/confidentialite', name: 'app_confidentialite')]
    public function confidentialite(): Response
    {
        return $this->render('pages/confidentialite.html.twig');
    }

    #[Route('/CGU', name: 'app_cgu')]
    public function cgu(): Response
    {
        return $this->render('pages/cgu.html.twig');
    }

}
