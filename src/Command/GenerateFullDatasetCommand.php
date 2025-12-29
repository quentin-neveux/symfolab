<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\Trajet;
use App\Entity\TrajetPassager;
use App\Entity\Review;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker\Factory;

#[AsCommand(
    name: 'app:generate-full-dataset',
    description: 'Génère une base EcoRide complète, cohérente et exploitable'
)]
class GenerateFullDatasetCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $faker = Factory::create('fr_FR');

        /* =====================================================
           CONFIG
        ===================================================== */
        $NB_USERS = 1500;

        $cities = [ /* ⬅️ TA LISTE DE VILLES INCHANGÉE */ ];

        $phones = ['+33', '+41', '+32', '+39'];
        $prefs  = ['oui', 'non', 'indifferent'];

        $users = [];

        /* =====================================================
           1) USERS + VEHICLES (BATCH)
        ===================================================== */
        for ($i = 1; $i <= $NB_USERS; $i++) {

            $user = new User();

            $prenom = $faker->firstName();
            $nom    = $faker->lastName();

            $user->setPrenom($prenom);
            $user->setNom($nom);
            $user->setEmail(strtolower("$prenom.$nom$i@ecoride.test"));
            $user->setPassword(
                $this->hasher->hashPassword($user, 'password123')
            );

            if ($i === 1) {
                $user->setRoles(['ROLE_ADMIN']);
            }

            $user->setTelephone(
                $phones[array_rand($phones)] . $faker->numerify('########')
            );

            $user->setDateNaissance(
                $faker->dateTimeBetween('-70 years', '-18 years')
            );

            $user->setBio($faker->sentence(15));
            $user->setAimlabBestAvg($faker->randomFloat(2, 80, 220));

            $user->setMusique($faker->randomElement($prefs));
            $user->setDiscussion($faker->randomElement($prefs));
            $user->setAnimaux($faker->randomElement($prefs));
            $user->setPausesCafe($faker->randomElement($prefs));
            $user->setFumeur($faker->randomElement($prefs));

            $user->setTokens(20);

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

            // 🔑 BATCH FLUSH USERS
            if ($i % 50 === 0) {
                $this->em->flush();
                $this->em->clear();
                $output->writeln("✔️ $i users générés");
            }
        }

        $this->em->flush();
        $this->em->clear();

        $output->writeln("✅ Users & véhicules terminés");

        /* =====================================================
           2) TRAJETS + PASSAGERS + REVIEWS (BATCH)
        ===================================================== */
        $counter = 0;

        foreach ($users as $conducteur) {

            if (random_int(0, 100) > 60) {
                continue;
            }

            $vehicle = $conducteur->getVehicles()->first();
            if (!$vehicle) {
                continue;
            }

            $nbTrajets = random_int(1, 3);

            for ($i = 0; $i < $nbTrajets; $i++) {

                $depart  = $faker->randomElement($cities);
                $arrivee = $faker->randomElement(
                    array_values(array_diff($cities, [$depart]))
                );

                $dateDepart = $faker->dateTimeBetween('-30 days', '+30 days');
                $isPast = $dateDepart < new \DateTimeImmutable();

                $trajet = new Trajet();
                $trajet->setVilleDepart($depart);
                $trajet->setVilleArrivee($arrivee);
                $trajet->setDateDepart($dateDepart);
                $trajet->setPrice($faker->randomFloat(2, 5, 35));
                $trajet->setPlacesDisponibles(max(1, $vehicle->getPlaces() - 1));
                $trajet->setConducteur($conducteur);
                $trajet->setVehicle($vehicle);
                $trajet->setConducteurConfirmeFin($isPast);

                $this->em->persist($trajet);

                $maxPassagers = min(3, $vehicle->getPlaces() - 1);
                if ($maxPassagers <= 0) {
                    continue;
                }

                $passagers = $faker->randomElements(
                    array_filter($users, fn ($u) => $u !== $conducteur),
                    random_int(1, $maxPassagers)
                );

                foreach ($passagers as $passagerUser) {

                    $tp = new TrajetPassager();
                    $tp->setPassager($passagerUser);
                    $tp->setIsPaid(true);
                    $tp->setPassagerConfirmeFin($isPast);

                    $trajet->addPassager($tp);

                    if ($isPast) {
                        $review = new Review();
                        $review->setAuthor($passagerUser);
                        $review->setTarget($conducteur);
                        $review->setTrajet($trajet);
                        $review->setRating($faker->numberBetween(3, 5));
                        $review->setComment(
                            $faker->boolean(70) ? $faker->sentence(10) : null
                        );

                        $this->em->persist($review);
                    }
                }

                $counter++;

                // 🔑 BATCH FLUSH TRAJETS
                if ($counter % 50 === 0) {
                    $this->em->flush();
                    $this->em->clear();
                    $output->writeln("🚗 $counter trajets générés");
                }
            }
        }

        $this->em->flush();
        $this->em->clear();

        $output->writeln('🎉 Base EcoRide générée avec succès');

        return Command::SUCCESS;
    }
}
