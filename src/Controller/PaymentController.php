<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PaymentController extends AbstractController
{
    #[Route('/payment', name: 'payment')]
    public function index(): Response
    {
        // tu peux plus tard y ajouter la logique de paiement / validation
        return $this->render('payment/payment.html.twig', [
            'user' => $this->getUser(),
        ]);
    }
}
