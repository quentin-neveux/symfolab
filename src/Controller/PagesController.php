<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PagesController extends AbstractController
{
    #[Route('/a-propos', name: 'app_apropos')]
    public function apropos(): Response
    {
        return $this->render('pages/a_propos.html.twig');
    }

    #[Route('/conseils', name: 'app_conseils')]
    public function conseils(): Response
    {
        return $this->render('pages/conseils.html.twig');
    }
}
