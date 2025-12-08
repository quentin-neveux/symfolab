<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TestLoaderController extends AbstractController
{
    #[Route('/test/loader', name: 'test_loader')]
    public function index(): Response
    {
        return $this->render('test/loader.html.twig');
    }
}
