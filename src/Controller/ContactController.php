<?php

namespace App\Controller;

use App\Form\ContactType;
use App\Service\MailerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request, MailerService $mailer): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();

            // 🔒 IMPORTANT : "email" est réservé dans TemplatedEmail
            if (isset($data['email'])) {
                $data['fromEmail'] = $data['email'];
                unset($data['email']);
            }

            $mailer->send(
                'support@ecoride.local',
                '📩 Nouveau message contact',
                'emails/passager/contact_message.html.twig',
                $data
            );

            $this->addFlash('success', 'Votre message a bien été envoyé ✔');
            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/contact.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
