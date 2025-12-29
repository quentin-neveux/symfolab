<?php

namespace App\Service;

use App\Entity\Trajet;
use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MailerService
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    // =========================================================
    // 🆕 INSCRIPTION UTILISATEUR
    // =========================================================
    public function sendInscriptionConfirmation(User $user): void
    {
        if (!$user->getEmail()) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address('noreply@ecoride.fr', 'EcoRide'))
            ->to($user->getEmail())
            ->subject('Bienvenue sur EcoRide 🌿')
            ->htmlTemplate('emails/passager/inscription_confirmation.html.twig')
            ->context([
                'user' => $user,
            ]);

        $this->mailer->send($email);
    }

    // =========================================================
    // 🚗 TRAJET CRÉÉ — mail conducteur
    // =========================================================
    public function notifyTrajetCreated(Trajet $trajet): void
    {
        $this->sendToConducteur(
            $trajet,
            'Votre trajet est en ligne 🚗',
            'emails/conducteur/trajet_created.html.twig'
        );
    }

    // =========================================================
    // 💳 RÉSERVATION + PAIEMENT — mail passager
    // =========================================================
    public function notifyReservationConfirmed(Trajet $trajet, User $passager): void
    {
    if (!$passager->getEmail()) {
        return;
    }

    $email = (new TemplatedEmail())
        ->from(new Address('noreply@ecoride.fr', 'EcoRide'))
        ->to($passager->getEmail())
        ->subject('Réservation confirmée — EcoRide')
        ->htmlTemplate('emails/passager/paiement_reservation_confirmation.html.twig')
        ->context([
            'trajet'    => $trajet,
            'user'      => $passager,
            'trajetUrl' => $this->urlGenerator->generate(
                'app_trajet_detail',
                ['id' => $trajet->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ]);

    $this->mailer->send($email);
    }


    // =========================================================
    // 👤 NOUVEAU PASSAGER — mail conducteur
    // =========================================================
    public function notifyNewPassenger(Trajet $trajet, User $passager): void
    {
        if ($trajet->getConducteur() === $passager) {
            return;
        }

        $this->sendToConducteur(
            $trajet,
            'Nouveau passager sur votre trajet 👤',
            'emails/conducteur/new_passenger.html.twig',
            [
                'passager' => $passager,
            ]
        );
    }

    // =========================================================
    // 🏁 TRAJET CLÔTURÉ — mail passagers
    // =========================================================
    public function notifyTrajetClosedToPassengers(Trajet $trajet): void
    {
        foreach ($trajet->getPassagers() as $reservation) {
            $passager = $reservation->getPassager();

            if (!$passager || !$passager->getEmail()) {
                continue;
            }

            $email = (new TemplatedEmail())
                ->from(new Address('noreply@ecoride.fr', 'EcoRide'))
                ->to($passager->getEmail())
                ->subject('Trajet terminé — EcoRide')
                ->htmlTemplate('emails/passager/trajet_closed.html.twig')
                ->context([
                    'trajet'    => $trajet,
                    'passager'  => $passager,
                    'trajetUrl' => $this->generateTrajetUrl($trajet),
                ]);

            $this->mailer->send($email);
        }
    }

    // =========================================================
    // 💰 PAIEMENT LIBÉRÉ — mail conducteur
    // =========================================================
    public function notifyPayoutReleased(Trajet $trajet, float $amount): void
    {
        $this->sendToConducteur(
            $trajet,
            'Paiement libéré 💰',
            'emails/conducteur/payout_released.html.twig',
            [
                'amount' => $amount,
            ]
        );
    }

    // =========================================================
    // ❌ ANNULATION PAR PASSAGER
    // =========================================================
    public function notifyCancellationByPassenger(Trajet $trajet, User $passager): void
    {
        // Mail PASSAGER
        if ($passager->getEmail()) {
            $email = (new TemplatedEmail())
                ->from(new Address('noreply@ecoride.fr', 'EcoRide'))
                ->to($passager->getEmail())
                ->subject('Réservation annulée — EcoRide')
                ->htmlTemplate('emails/passager/reservation_annulee.html.twig')
                ->context([
                    'trajet'    => $trajet,
                    'passager'  => $passager,
                    'trajetUrl' => $this->generateTrajetUrl($trajet),
                ]);

            $this->mailer->send($email);
        }

        // Mail CONDUCTEUR
        $this->sendToConducteur(
            $trajet,
            'Un passager a annulé sa réservation',
            'emails/conducteur/passager_annulation.html.twig',
            [
                'passager' => $passager,
            ]
        );
    }

    // =========================================================
    // ❌ ANNULATION PAR CONDUCTEUR
    // =========================================================
    public function notifyCancellationByConducteur(Trajet $trajet): void
    {
        $conducteur = $trajet->getConducteur();

        // Mail CONDUCTEUR
        if ($conducteur && $conducteur->getEmail()) {
            $email = (new TemplatedEmail())
                ->from(new Address('noreply@ecoride.fr', 'EcoRide'))
                ->to($conducteur->getEmail())
                ->subject('Trajet annulé — EcoRide')
                ->htmlTemplate('emails/conducteur/trajet_annule_conducteur.html.twig')
                ->context([
                    'trajet'     => $trajet,
                    'conducteur' => $conducteur,
                    'trajetUrl'  => $this->generateTrajetUrl($trajet),
                ]);

            $this->mailer->send($email);
        }

        // Mail PASSAGERS
        foreach ($trajet->getPassagers() as $reservation) {
            $passager = $reservation->getPassager();

            if (!$passager || !$passager->getEmail()) {
                continue;
            }

            $email = (new TemplatedEmail())
                ->from(new Address('noreply@ecoride.fr', 'EcoRide'))
                ->to($passager->getEmail())
                ->subject('Trajet annulé par le conducteur — EcoRide')
                ->htmlTemplate('emails/passager/trajet_annule_par_conducteur.html.twig')
                ->context([
                    'trajet'    => $trajet,
                    'passager'  => $passager,
                    'trajetUrl' => $this->generateTrajetUrl($trajet),
                ]);

            $this->mailer->send($email);
        }
    }

    // =========================================================
    // 🧠 UTILITAIRE — mail conducteur
    // =========================================================
    private function sendToConducteur(
        Trajet $trajet,
        string $subject,
        string $template,
        array $context = []
    ): void {
        $conducteur = $trajet->getConducteur();

        if (!$conducteur || !$conducteur->getEmail()) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address('noreply@ecoride.fr', 'EcoRide'))
            ->to($conducteur->getEmail())
            ->subject($subject)
            ->htmlTemplate($template)
            ->context(array_merge([
                'trajet'     => $trajet,
                'conducteur' => $conducteur,
                'trajetUrl'  => $this->generateTrajetUrl($trajet),
            ], $context));

        $this->mailer->send($email);
    }

    private function generateTrajetUrl(Trajet $trajet): string
    {
        return $this->urlGenerator->generate(
            'app_trajet_detail',
            ['id' => $trajet->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    // =========================================================
    // 📩 CONTACT — ENVOI GÉNÉRIQUE
    // =========================================================
    public function send(
        string $to,
        string $subject,
        string $template,
        array $context = []
    ): void {
        $email = (new TemplatedEmail())
            ->from(new Address('noreply@ecoride.fr', 'EcoRide'))
            ->to($to)
            ->subject($subject)
            ->htmlTemplate($template)
            ->context($context);
    
        $this->mailer->send($email);
    }

}