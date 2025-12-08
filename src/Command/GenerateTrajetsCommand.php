<?php

namespace App\Command;

use App\Entity\Trajet;
use App\Entity\User;
use App\Service\DistanceCalculator;
use App\Service\TokenCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Faker\Factory;

#[AsCommand(
    name: 'app:generate-trajets',
    description: 'Génère des trajets factices liés à certains utilisateurs'
)]
class GenerateTrajetsCommand extends Command
{
    private EntityManagerInterface $em;
    private DistanceCalculator $distanceCalc;
    private TokenCalculator $tokenCalc;

    public function __construct(
        EntityManagerInterface $em,
        DistanceCalculator $distanceCalc,
        TokenCalculator $tokenCalc
    ) {
        parent::__construct();
        $this->em = $em;
        $this->distanceCalc = $distanceCalc;
        $this->tokenCalc = $tokenCalc;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $faker = Factory::create('fr_FR');

        /* 1) USERS */
        $users = $this->em->getRepository(User::class)->findAll();
        if (!$users) {
            $output->writeln("❌ Aucun utilisateur trouvé.");
            return Command::FAILURE;
        }

        /* 2) Sélection conducteurs */
        $conducteurs = array_filter($users, fn() => $faker->boolean(25));
        if (empty($conducteurs)) {
            $output->writeln("❌ Aucun conducteur sélectionné.");
            return Command::FAILURE;
        }

        $output->writeln("🚗 Conducteurs disponibles : " . count($conducteurs));

        /* 3) Villes */
        $villes = [
            "Paris","Lyon","Marseille","Toulouse","Nice","Nantes",
            "Genève","Lausanne","Zurich","Berne",
            "Bruxelles","Anvers","Gand","Liège",
            "Milan","Turin","Rome","Florence","Venise"
        ];

        /* 4) Génération */
        $nb = 200;

        for ($i = 0; $i < $nb; $i++) {

            $trajet = new Trajet();

            // Villes cohérentes
            $depart = $faker->randomElement($villes);
            $arrivee = $faker->randomElement(array_filter($villes, fn($v) => $v !== $depart));

            $trajet->setVilleDepart($depart);
            $trajet->setVilleArrivee($arrivee);

            // Dates
            $dateDepart = $faker->dateTimeBetween('-10 days', '+15 days');
            $trajet->setDateDepart($dateDepart);

            // Durée
            $minutes = $faker->numberBetween(30, 360);
            $duration = (new \DateTime())->setTime(0, 0)->modify("+$minutes minutes");

            $trajet->setDuree($duration);
            $trajet->setDateArrivee((clone $dateDepart)->modify("+$minutes minutes"));

            // Conducteur
            $trajet->setConducteur($faker->randomElement($conducteurs));

            // Places
            $trajet->setPlacesDisponibles($faker->numberBetween(1, 4));

            // Véhicules
            $vehicules = ["Citadine", "Berline", "SUV", "Break", "Van"];
            $trajet->setTypeVehicule($faker->randomElement($vehicules));

            // Énergie
            $energies = ["essence", "diesel", "electrique", "hybride"];
            $energie = $faker->randomElement($energies);

            $trajet->setEnergie($energie);
            $trajet->setEstEcologique(in_array($energie, ["electrique", "hybride"]));

            // 🔥 TOKENS : calcul réel basé sur la distance
            $distance = $this->distanceCalc->estimateDistance($depart, $arrivee);
            $tokenCost = $this->tokenCalc->calculate($distance);

            $trajet->setTokenCost($tokenCost);

            // Commentaire
            $trajet->setCommentaire($faker->boolean(50) ? $faker->sentence(10) : null);

            // Fin de trajet
            $trajet->setConducteurConfirmeFin(false);

            $this->em->persist($trajet);

            if ($i % 25 === 0) {
                $this->em->flush();
            }
        }

        $this->em->flush();

        $output->writeln("✅ $nb trajets générés avec le VRAI calcul de tokens !");
        return Command::SUCCESS;
    }
}
