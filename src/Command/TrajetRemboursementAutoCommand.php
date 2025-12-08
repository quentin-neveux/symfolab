<?php

namespace App\Command;

use App\Entity\Trajet;
use App\Entity\TrajetPassager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'ecoride:trajets:remboursements',
    description: 'Rembourse automatiquement les passagers si le trajet est passé non effectué.'
)]
class TrajetRemboursementAutoCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = new \DateTime();

        $trajets = $this->em->getRepository(Trajet::class)
            ->createQueryBuilder('t')
            ->where('t.dateArrivee < :now')
            ->andWhere('t.rembourseAuto = false')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();

        foreach ($trajets as $trajet) {

            $reservations = $this->em->getRepository(TrajetPassager::class)
                ->findBy(['trajet' => $trajet]);

            foreach ($reservations as $res) {
                if ($res->isPaid()) {
                    $user = $res->getPassager();
                    $user->setTokens($user->getTokens() + $trajet->getTokenCost());
                }
            }

            $trajet->setRembourseAuto(true);
        }

        $this->em->flush();

        return Command::SUCCESS;
    }
}
