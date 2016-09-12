<?php

namespace Pim\Bundle\DevToolboxBundle\Remover;

use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * HistoryRemover for ORM
 *
 * @author Remy Betus <remy.betus@akeneo.com>
 */
class ORMHistoryRemover extends AbstractHistoryRemover
{
    /**
     * {@inheritdoc}
     */
    public function purgeHistory(OutputInterface $output, $months, $maximumVersion)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('v.id');
        $qb->from('Pim\Bundle\VersioningBundle\Model\Version', 'v');

        $this->excludePublishedVersions($qb);
        if ($months > 0) {
            $this->selectVersionsBefore($qb, $months);
        }
        if ($maximumVersion > 0) {
            $this->selectExtraVersions($qb, $maximumVersion);
        }
        $versions = $qb->getQuery()->getResult();

        if (count($versions) === 0) {
            $output->writeln('<info>No version to purge.</info>');
            return;
        }

        //   $this->purge($versions);
    }

    /**
     * Purges the history of versions
     *
     * @param array $versions
     */
    public function purge(array $versions)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb
            ->delete('Pim\Bundle\VersioningBundle\Model\Version', 'v')
            ->where($qb->expr()->in('v.id', ':version_ids'))
            ->setParameter('version_ids', $versions);

        $qb->getQuery()->execute();
    }

    /**
     * Excludes versions linked to published product from the set of version
     * that can be removed.
     *
     * @param QueryBuilder $queryBuilder
     */
    public function excludePublishedVersions(QueryBuilder $queryBuilder)
    {
        $publishedVersionIds = $this->getPublishedVersionIds();

        $queryBuilder
            ->andWhere($queryBuilder->expr()->notIn('v.id', ':published_version_ids'))
            ->setParameter('published_version_ids', $publishedVersionIds);
    }

    /**
     * Filters on version that are logged before a given number of months
     *
     * @param QueryBuilder $queryBuilder
     * @param int          $months
     */
    public function selectVersionsBefore(QueryBuilder $queryBuilder, $months)
    {
        $before = new \DateTime('now', new \DateTimeZone('UTC'));
        $before->modify('-' . intval($months) . ' months');
        $queryBuilder
            ->andWhere($queryBuilder->expr()->gt('v.loggedAt', ':logged_at'))
            ->setParameter('logged_at', $before);
    }

    /**
     * Select only the extra version of a product
     *
     * @param QueryBuilder $queryBuilder
     * @param              $maximumVersion
     */
    public function selectExtraVersions(QueryBuilder $queryBuilder, $maximumVersion)
    {
        $connection = $this->entityManager->getConnection();

        $query = <<<SQL
        SELECT
  v0.id,
  v0.resource_id,
  v0.resource_name,
  v0.version
FROM (
       SELECT
         v1.resource_name  AS resource_name,
         v1.resource_id    AS resource_id,
         count(v1.version) AS number_version
       FROM pim_versioning_version v1
       GROUP BY resource_id, resource_name
       HAVING number_version > :maximum_version
     ) AS v2, pim_versioning_version v0
WHERE
  v0.resource_name = v2.resource_name
  AND v0.resource_id = v2.resource_id
  AND v0.version <= (v2.number_version - :maximum_version)
;
SQL;
        $statement = $connection->prepare($query);
        $statement->bindParam(':maximum_version', $maximumVersion, \PDO::PARAM_INT);
        $statement->execute();

        $versions = $statement->fetchAll();
    }

    /**
     * Gets the id of the versions that are linked to a published product
     * so they cannot be removed.
     *
     * @return int[]
     */
    public function getPublishedVersionIds()
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb
            ->select('IDENTITY(pp.version) AS version_id')
            ->from('PimEnterprise\Bundle\WorkflowBundle\Model\PublishedProduct', 'pp');

        return $qb->getQuery()->getResult();
    }
}
