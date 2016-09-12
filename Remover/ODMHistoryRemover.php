<?php

namespace Pim\Bundle\DevToolboxBundle\Remover;

use Doctrine\ODM\MongoDB\Query\Query;
use Symfony\Component\Console\Output\OutputInterface;

class ODMHistoryRemover extends AbstractHistoryRemover
{
    public function purgeHistory(OutputInterface $output, $months, $maximumVersion)
    {
        $versionRepository = $this->managerRegistry->getRepository('Pim\Bundle\VersioningBundle\Model\Version');
        $all = $versionRepository->findAll();
        $count = number_format(count($all), 0, '.', ' ');
        $output->writeln("<info>Purging history... $count version found !</info>");

        $queryBuilder = $versionRepository->createQueryBuilder();

        $this->excludePublishedVersions($queryBuilder);
        $this->selectVersionsBefore($queryBuilder, $months);
        $this->selectExtraVersions($queryBuilder, $maximumVersion);

        $queryBuilder->getQuery()->execute();
    }

    public function excludePublishedVersions(Query $query)
    {

    }

    public function selectVersionsBefore(Query $query, $months)
    {

    }

    public function selectExtraVersions(Query $query, $maximumVersion)
    {

    }
}
