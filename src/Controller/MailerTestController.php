<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailerTestController extends AbstractController
{
    #[Route('/test-mail', name: 'test_mail')]
    public function testMail(MailerInterface $mailer): Response
    {
        dump("➡️ Controller OK : on entre bien dans la route");

        $email = (new Email())
            ->from('test@example.com')
            ->to('admin@ecoride.local')
            ->subject('TEST MAILPIT ✔')
            ->text("Ceci est un test envoyé via Symfony → Mailpit.");

        try {
            $mailer->send($email);
            dump("📨 MAIL ENVOYÉ ✔");
        } catch (\Throwable $e) {
            dump("❌ ERREUR SMTP", $e->getMessage());
        }

        return new Response("<h1>Test mail exécuté — vérifie Mailpit.</h1>");
    }
}
