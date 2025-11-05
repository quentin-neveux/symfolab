<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CovoiturerController extends AbstractController
{
    #[Route('/covoiturer', name: 'app_covoiturer')]
    public function index(): Response
    {
        return $this->render('covoiturer/covoiturer.html.twig', [
            'page_name' => 'Covoiturer',
        ]);
    }
}
