<?php

namespace App\Service;

use App\Entity\Dispute;
use App\Entity\Trajet;
use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MailerService
{
    private Address $from;

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        string $fromEmail = 'noreply@ecoride.fr',
        string $fromName = 'EcoRide'
    ) {
        $this->from = new Address($fromEmail, $fromName);
    }

    // =========================================================
    // 🆕 INSCRIPTION UTILISATEUR
    // =========================================================
    public function sendInscriptionConfirmation(User $user): void
    {
        if (!$user->getEmail()) {
            return;
        }

        $email = $this->createEmail()
            ->to($user->getEmail())
            ->subject('Bienvenue sur EcoRide 🌿')
            ->htmlTemplate('emails/passager/inscription_confirmation.html.twig')
            ->context([
                'prenom' => (string) ($user->getPrenom() ?? ''),
            ]);

        $this->mailer->send($email);
    }

    // =========================================================
    // 🚗 TRAJET CRÉÉ — mail conducteur
    // =========================================================
    public function notifyTrajetCreated(Trajet $trajet): void
    {
        $conducteur = $trajet->getConducteur();
        if (!$conducteur || !$conducteur->getEmail()) {
            return;
        }

        $trajetId = (int) $trajet->getId();

        $email = $this->createEmail()
            ->to($conducteur->getEmail())
            ->subject('Votre trajet est en ligne 🚗')
            ->htmlTemplate('emails/conducteur/trajet_created.html.twig')
            ->context([
                'conducteurPrenom' => (string) ($conducteur->getPrenom() ?? ''),
                'trajetId'         => $trajetId,
                'villeDepart'      => (string) ($trajet->getVilleDepart() ?? ''),
                'villeArrivee'     => (string) ($trajet->getVilleArrivee() ?? ''),
                'dateDepart'       => $trajet->getDateDepart()?->format('d/m/Y H:i'),
                'tokenCost'        => (int) ($trajet->getTokenCost() ?? 0),
                'trajetUrl'        => $this->generateTrajetUrl($trajet),
            ]);

        $this->mailer->send($email);
    }

    // =========================================================
    // 💳 RÉSERVATION + PAIEMENT — mail passager
    // =========================================================
    public function notifyReservationConfirmed(Trajet $trajet, User $passager): void
    {
        if (!$passager->getEmail()) {
            return;
        }

        $email = $this->createEmail()
            ->to($passager->getEmail())
            ->subject('Réservation confirmée — EcoRide')
            ->htmlTemplate('emails/passager/paiement_reservation_confirmation.html.twig')
            ->context([
                'trajet'    => $trajet,
                'user'      => $passager,
                'trajetUrl' => $this->generateTrajetUrl($trajet),
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

            $email = $this->createEmail()
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
    // 💰 PAIEMENT LIBÉRÉ — mail conducteur (TOKENS)
    // =========================================================
    public function notifyPayoutReleased(Trajet $trajet, int $amount): void
    {
        $conducteur = $trajet->getConducteur();

        if (!$conducteur || !$conducteur->getEmail()) {
            return;
        }

        $email = $this->createEmail()
            ->to($conducteur->getEmail())
            ->subject('Paiement libéré — EcoRide')
            ->htmlTemplate('emails/conducteur/payout_released.html.twig')
            ->context([
                'trajet' => $trajet,
                'amount' => $amount,
                'url'    => $this->generateTrajetUrl($trajet),
            ]);

        $this->mailer->send($email);
    }

    // =========================================================
    // 💰 PAIEMENT LIBÉRÉ — mail passagers (INFO)
    // =========================================================
    public function notifyPayoutReleasedToPassengers(Trajet $trajet, int $amount): void
    {
        foreach ($trajet->getPassagers() as $reservation) {
            $passager = $reservation->getPassager();

            if (!$passager || !$passager->getEmail()) {
                continue;
            }

            // Notifier uniquement les réservations payées
            if (method_exists($reservation, 'isPaid') && !$reservation->isPaid()) {
                continue;
            }

            $email = $this->createEmail()
                ->to($passager->getEmail())
                ->subject('Paiement libéré au conducteur — EcoRide')
                ->htmlTemplate('emails/passager/payout_released_info.html.twig')
                ->context([
                    'trajet'    => $trajet,
                    'passager'  => $passager,
                    'amount'    => $amount,
                    'trajetUrl' => $this->generateTrajetUrl($trajet),
                ]);

            $this->mailer->send($email);
        }
    }

    // =========================================================
    // ❌ ANNULATION PAR PASSAGER
    // =========================================================
    public function notifyCancellationByPassenger(Trajet $trajet, User $passager): void
    {
        // Mail PASSAGER
        if ($passager->getEmail()) {
            $email = $this->createEmail()
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
            $email = $this->createEmail()
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

            $email = $this->createEmail()
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
    // 🚨 DISPUTE — confirmation au reporter
    // =========================================================
    public function notifyDisputeCreated(Dispute $dispute): void
    {
        $reporter = $dispute->getReporter();
        $trajet   = $dispute->getTrajet();

        if (!$reporter || !$trajet || !$reporter->getEmail()) {
            return;
        }

        $email = $this->createEmail()
            ->to($reporter->getEmail())
            ->subject('Signalement envoyé — EcoRide')
            ->htmlTemplate('emails/passager/dispute_created.html.twig')
            ->context([
                'dispute'   => $dispute,
                'trajet'    => $trajet,
                'user'      => $reporter,
                'reporter'  => $reporter,
                'target'    => $dispute->getTarget(),
                'trajetUrl' => $this->generateTrajetUrl($trajet),
            ]);

        $this->mailer->send($email);
    }

    // =========================================================
    // 🚨 DISPUTE — notification employé (interne)
    // =========================================================
    public function notifyEmployeNewDispute(Dispute $dispute, string $toEmployeEmail): void
    {
        $trajet = $dispute->getTrajet();

        if (!$trajet || trim($toEmployeEmail) === '') {
            return;
        }

        $email = $this->createEmail()
            ->to($toEmployeEmail)
            ->subject('Nouveau signalement à traiter — EcoRide')
            ->htmlTemplate('emails/admin/new_dispute.html.twig')
            ->context([
                'dispute'  => $dispute,
                'trajet'   => $trajet,
                'reporter' => $dispute->getReporter(),
                'target'   => $dispute->getTarget(),
                'adminUrl' => $this->urlGenerator->generate(
                    'admin_dispute_show',
                    ['id' => $dispute->getId()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
            ]);

        $this->mailer->send($email);
    }

    // =========================================================
    // 🚨 DISPUTE — statut mis à jour (reporter)
    // =========================================================
    public function notifyDisputeStatusChanged(Dispute $dispute): void
    {
        $reporter = $dispute->getReporter();
        $trajet   = $dispute->getTrajet();

        if (!$reporter || !$trajet || !$reporter->getEmail()) {
            return;
        }

        $email = $this->createEmail()
            ->to($reporter->getEmail())
            ->subject('Mise à jour de votre signalement — EcoRide')
            ->htmlTemplate('emails/passager/dispute_status_changed.html.twig')
            ->context([
                'dispute'   => $dispute,
                'trajet'    => $trajet,
                'user'      => $reporter,
                'reporter'  => $reporter,
                'target'    => $dispute->getTarget(),
                'trajetUrl' => $this->generateTrajetUrl($trajet),
            ]);

        $this->mailer->send($email);
    }

    // =========================================================
    // 📩 CONTACT — ENVOI GÉNÉRIQUE
    // =========================================================
    public function send(string $to, string $subject, string $template, array $context = []): void
    {
        if (trim($to) === '') {
            return;
        }

        $email = $this->createEmail()
            ->to($to)
            ->subject($subject)
            ->htmlTemplate($template)
            ->context($context);

        $this->mailer->send($email);
    }

    // =========================================================
    // 🧠 UTILITAIRES
    // =========================================================
    private function createEmail(): TemplatedEmail
    {
        return (new TemplatedEmail())->from($this->from);
    }

    private function sendToConducteur(Trajet $trajet, string $subject, string $template, array $context = []): void
    {
        $conducteur = $trajet->getConducteur();

        if (!$conducteur || !$conducteur->getEmail()) {
            return;
        }

        $email = $this->createEmail()
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
            ['id' => (int) $trajet->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }
}
