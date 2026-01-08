<?php

namespace App\Command;

use App\Entity\Review;
use App\Entity\Trajet;
use App\Entity\TrajetPassager;
use App\Entity\User;
use App\Entity\Vehicle;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:generate-full-dataset',
    description: 'Génère une base EcoRide complète (tokens entiers, trajets cohérents, reviews, Annecy/Genève inclus)'
)]
class GenerateFullDatasetCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $faker = Factory::create('fr_FR');
        $faker->seed(1234);

        /* =====================================================
           CONFIG
        ===================================================== */
        $NB_USERS = 500;
        $TARGET_TRAJETS = 2000;

        // Fenêtre de dates : -7 / +30
        $DATE_FROM = '-7 days';
        $DATE_TO   = '+30 days';

        // Villes (inclut OBLIGATOIREMENT Annecy & Genève)
        $cities = [
            'Annecy', 'Genève',
            'Lyon', 'Grenoble', 'Chambéry', 'Aix-les-Bains',
            'Paris', 'Dijon', 'Besançon', 'Strasbourg',
            'Marseille', 'Nice', 'Montpellier', 'Toulouse',
            'Bordeaux', 'Nantes', 'Rennes', 'Lille',
            'Lausanne', 'Fribourg', 'Neuchâtel', 'Zurich'
        ];

        $phones = ['+33', '+41', '+32', '+39'];
        $prefs  = ['oui', 'non', 'indifferent'];

        // Distribution de tokens réaliste (plus de 3..8, peu de 1 ou 15)
        $tokenWeights = [1,2,2,3,3,4,4,5,5,6,6,7,7,8,8,9,10,12,15];

        $users = [];

        /* =====================================================
           1) USERS + VEHICLES (batch)
        ===================================================== */
        $output->writeln("👤 Génération de {$NB_USERS} users…");

        for ($i = 1; $i <= $NB_USERS; $i++) {
            $user = new User();

            $prenom = $faker->firstName();
            $nom    = $faker->lastName();

            $user->setPrenom($prenom);
            $user->setNom($nom);
            $user->setEmail(strtolower("$prenom.$nom$i@ecoride.test"));
            $user->setPassword($this->hasher->hashPassword($user, 'password123'));

            if ($i === 1) {
                $user->setRoles(['ROLE_ADMIN']);
            }

            $user->setTelephone($phones[array_rand($phones)] . $faker->numerify('########'));
            $user->setDateNaissance($faker->dateTimeBetween('-70 years', '-18 years'));

            // ✅ aucune bio
            $user->setBio(null);

            // préférences
            $user->setMusique($faker->randomElement($prefs));
            $user->setDiscussion($faker->randomElement($prefs));
            $user->setAnimaux($faker->randomElement($prefs));
            $user->setPausesCafe($faker->randomElement($prefs));
            $user->setFumeur($faker->randomElement($prefs));

            // tokens de départ
            $user->setTokens(20);

            // véhicule (1 par user)
            $vehicle = new Vehicle();
            $vehicle->setMarque($faker->randomElement([
                'Peugeot','Renault','Citroën','Dacia',
                'Volkswagen','Audi','BMW','Mercedes-Benz',
                'Toyota','Ford','Fiat','Opel',
                'Hyundai','Kia','Nissan','Mazda','Honda',
                'Seat','Škoda','Volvo',
                'Mini','Suzuki','Jeep','Mitsubishi',
                'Tesla','DS','Cupra','Smart','MG'
            ]));
            $vehicle->setModele(ucfirst($faker->word()));
            $vehicle->setImmatriculation(strtoupper($faker->bothify('??-###-??')));
            $vehicle->setEnergie($faker->randomElement(['Essence','Diesel','Électrique']));
            $vehicle->setPlaces($faker->numberBetween(2, 4));

            $user->addVehicle($vehicle);

            $this->em->persist($user);
            $this->em->persist($vehicle);

            $users[] = $user;

            if ($i % 50 === 0) {
                $this->em->flush();
                $this->em->clear();
                $output->writeln("✔️ {$i} users générés");
            }
        }

        $this->em->flush();
        $this->em->clear();
        $output->writeln("✅ Users & véhicules terminés");

        /* =====================================================
           IMPORTANT : après clear(), on doit recharger les users
        ===================================================== */
        $userRepo = $this->em->getRepository(User::class);
        $users = $userRepo->findAll();

        /* =====================================================
           2) TRAJETS + PASSAGERS + REVIEWS
           - cible ~2000 trajets
           - Annecy/Genève garantis + plusieurs trajets même jour
        ===================================================== */
        $output->writeln("🚗 Génération d'environ {$TARGET_TRAJETS} trajets…");

        $trajetCount = 0;

        // --- A) “Pack” de trajets Annecy <-> Genève le même jour (tests)
        $today = new \DateTimeImmutable('today');
        $times = ['07:30', '08:15', '09:00', '17:30', '18:15'];

        for ($k = 0; $k < 12; $k++) { // 12 trajets A<->G
            $conducteur = $users[array_rand($users)];
            $vehicle = $conducteur->getVehicles()->first();
            if (!$vehicle) {
                continue;
            }

            $depart  = ($k % 2 === 0) ? 'Annecy' : 'Genève';
            $arrivee = ($depart === 'Annecy') ? 'Genève' : 'Annecy';

            $time = $times[$k % count($times)];
            $dateDepart = new \DateTimeImmutable($today->format('Y-m-d') . ' ' . $time);

            $trajet = new Trajet();
            $trajet->setVilleDepart($depart);
            $trajet->setVilleArrivee($arrivee);
            $trajet->setDateDepart($dateDepart);
            $trajet->setVehicle($vehicle);
            $trajet->setConducteur($conducteur);
            $trajet->setPlacesDisponibles(max(0, $vehicle->getPlaces() - 1));

            // 🎯 ton cas de test : 5 tokens pour Annecy <-> Genève
            $trajet->setTokenCost(5);

            // passé / futur
            $isPast = $dateDepart < new \DateTimeImmutable();
            $trajet->setConducteurConfirmeFin($isPast);

            $this->em->persist($trajet);
            $trajetCount++;

            if ($trajetCount % 50 === 0) {
                $this->em->flush();
                $this->em->clear();
                $output->writeln("🚗 {$trajetCount} trajets générés");
                $users = $userRepo->findAll(); // recharger après clear
            }
        }

        // --- B) Génération jusqu'à la cible
        while ($trajetCount < $TARGET_TRAJETS) {
            $conducteur = $users[array_rand($users)];

            // 60% des users deviennent conducteurs de temps en temps
            if (random_int(0, 100) > 60) {
                continue;
            }

            $vehicle = $conducteur->getVehicles()->first();
            if (!$vehicle) {
                continue;
            }

            $depart  = $faker->randomElement($cities);
            $arrivee = $faker->randomElement(array_values(array_diff($cities, [$depart])));

            // dates -7/+30
            $dateDepart = $faker->dateTimeBetween($DATE_FROM, $DATE_TO);
            $isPast = $dateDepart < new \DateTimeImmutable();

            $trajet = new Trajet();
            $trajet->setVilleDepart($depart);
            $trajet->setVilleArrivee($arrivee);
            $trajet->setDateDepart($dateDepart);
            $trajet->setVehicle($vehicle);
            $trajet->setConducteur($conducteur);
            $trajet->setPlacesDisponibles(max(0, $vehicle->getPlaces() - 1));
            $trajet->setConducteurConfirmeFin($isPast);

            // ✅ coût tokens UNIQUEMENT (1..15)
            // Force Annecy/Genève à 5 tokens (même hors pack)
            if (
                ($depart === 'Annecy' && $arrivee === 'Genève') ||
                ($depart === 'Genève' && $arrivee === 'Annecy')
            ) {
                $trajet->setTokenCost(5);
            } else {
                $trajet->setTokenCost((int) $faker->randomElement($tokenWeights));
            }

            $this->em->persist($trajet);

            // passagers (0..places) + reviews si past
            $maxPassagers = min(3, max(0, $vehicle->getPlaces() - 1));
            if ($maxPassagers > 0) {
                $nbPassagers = random_int(0, $maxPassagers);

                if ($nbPassagers > 0) {
                    // sélection passagers (différents du conducteur)
                    $pool = array_values(array_filter($users, fn($u) => $u->getId() !== $conducteur->getId()));
                    $passagers = $faker->randomElements($pool, $nbPassagers);

                    foreach ($passagers as $passagerUser) {
                        $tp = new TrajetPassager();
                        $tp->setPassager($passagerUser);
                        $tp->setIsPaid(true);
                        $tp->setPassagerConfirmeFin($isPast);

                        // snapshot des coûts (si tes champs existent)
                        if (method_exists($tp, 'setTokenCostCharged')) {
                            $tp->setTokenCostCharged($trajet->getTokenCost());
                        }
                        if (method_exists($tp, 'setPlatformFeeCharged')) {
                            $tp->setPlatformFeeCharged(Trajet::PLATFORM_FEE_TOKENS);
                        }

                        $trajet->addPassager($tp);

                        if ($isPast) {
                            $review = new Review();
                            $review->setAuthor($passagerUser);
                            $review->setTarget($conducteur);
                            $review->setTrajet($trajet);
                            $review->setRating($faker->numberBetween(3, 5));
                            $review->setComment($faker->boolean(70) ? $faker->sentence(10) : null);

                            $this->em->persist($review);
                        }
                    }
                }
            }

            $trajetCount++;

            if ($trajetCount % 50 === 0) {
                $this->em->flush();
                $this->em->clear();
                $output->writeln("🚗 {$trajetCount} trajets générés");
                $users = $userRepo->findAll(); // recharger après clear
            }
        }

        $this->em->flush();
        $this->em->clear();

        $output->writeln("🎉 Base EcoRide générée avec succès ({$TARGET_TRAJETS} trajets)");

        return Command::SUCCESS;
    }
}
