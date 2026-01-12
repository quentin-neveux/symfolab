<?php

namespace App\Command;

use App\Entity\City;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:import:geonames-cities',
    description: 'Import GeoNames cities500 into City table (France + Europe).'
)]
class ImportGeonamesCitiesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = '/tmp/geonames/cities500.txt';
        if (!is_file($file)) {
            $output->writeln("<error>File not found: {$file}</error>");
            return Command::FAILURE;
        }

        // Europe + France (incluse) : liste ISO2
        $allowedCountries = array_flip([
            'FR','BE','CH','DE','IT','ES','PT','NL','LU','IE','GB',
            'AT','DK','SE','NO','FI','IS',
            'PL','CZ','SK','HU','SI','HR','BA','RS','ME','MK','AL','GR','BG','RO','MD','UA','BY','LT','LV','EE',
            'AD','MC','SM','VA','LI','MT','CY','TR','RU'
        ]);

        $handle = fopen($file, 'rb');
        if (!$handle) {
            $output->writeln("<error>Cannot open file</error>");
            return Command::FAILURE;
        }

        $repo = $this->em->getRepository(City::class);

        $count = 0;
        $kept = 0;

        while (($line = fgets($handle)) !== false) {
            $count++;

            // GeoNames TSV fields (cities500)
            // 0 geonameid, 1 name, 2 asciiname, 3 alternatenames, 4 lat, 5 lon,
            // 6 feature class, 7 feature code, 8 country code, ... 14 population
            $cols = explode("\t", rtrim($line, "\r\n"));
            if (count($cols) < 15) continue;

            $name = $cols[1];
            $nameAscii = $cols[2];
            $lat = $cols[4];
            $lon = $cols[5];
            $country = $cols[8];
            $population = (int) $cols[14];

            if (!isset($allowedCountries[$country])) continue;

            // Insert
            $city = new City();
            $city->setName($name);
            $city->setNameAscii($nameAscii);
            $city->setCountryCode($country);
            $city->setPopulation($population);
            $city->setLat($lat);
            $city->setLon($lon);

            $this->em->persist($city);
            $kept++;

            if (($kept % 1000) === 0) {
                $this->em->flush();
                $this->em->clear();
                $output->writeln("Imported {$kept} cities...");
            }
        }

        fclose($handle);

        $this->em->flush();
        $this->em->clear();

        $output->writeln("<info>Done. Read {$count} lines, imported {$kept} cities.</info>");

        return Command::SUCCESS;
    }
}
