<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker\Factory;

#[AsCommand(
    name: 'app:generate-users',
    description: 'Génère des utilisateurs factices pour EcoRide'
)]
class GenerateUsersCommand extends Command
{
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;

    public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $hasher)
    {
        parent::__construct();
        $this->em = $em;
        $this->hasher = $hasher;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $faker = Factory::create('fr_FR');

        $nbUsers = 50; // tu peux changer

        for ($i = 0; $i < $nbUsers; $i++) {

            $user = new User();

            // ----------- Champs obligatoires -----------
            $user->setEmail($faker->unique()->email());
            $user->setRoles(['ROLE_USER']);

            $hashed = $this->hasher->hashPassword($user, 'password123');
            $user->setPassword($hashed);

            $user->setPrenom($faker->firstName());
            $user->setNom($faker->lastName());

            
            // ----------- Champs optionnels mais existants dans ta table -----------

            // Photo → null (laisser vide)
            $user->setPhoto(null);

            // Téléphone réaliste
            $indicatifs = ['+33', '+41', '+32', '+39'];
            $user->setTelephone($indicatifs[array_rand($indicatifs)] . $faker->numerify('########'));

            // Date de naissance 18–70 ans
            $user->setDateNaissance(
                $faker->dateTimeBetween('-70 years', '-18 years')
            );

            // Bio
            $user->setBio($faker->sentence(12));

            // Aimlab best avg
            $user->setAimlabBestAvg($faker->randomFloat(2, 50, 250));

            // Préférences
            $choices = ['oui', 'non', 'indifferent'];

            $user->setMusique($faker->randomElement($choices));
            $user->setDiscussion($faker->randomElement($choices));
            $user->setAnimaux($faker->randomElement($choices));
            $user->setPausesCafe($faker->randomElement($choices));
            $user->setFumeur($faker->randomElement($choices));

            // Tokens initiaux
            $user->setTokens($faker->numberBetween(0, 500));

            $this->em->persist($user);
        }

        $this->em->flush();

        $output->writeln("✅ $nbUsers utilisateurs factices créés avec succès.");

        return Command::SUCCESS;
    }
}
