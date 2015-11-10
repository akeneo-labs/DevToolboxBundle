<?php

namespace Pim\Bundle\DevToolboxBundle\Remover;

use Akeneo\Bundle\StorageUtilsBundle\Doctrine\SmartManagerRegistry;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Purges the history of versionable entities.
 * This includes:
 * - JobInstance
 * - AssociationType
 * - Attribute
 * - AttributeGroup
 * - Category
 * - Channel
 * - Family
 * - Group
 * - Locale
 * - Product
 *
 * Two strategies are available and can be used together:
 * - purge all versions older than n months
 * - purge extra version so each entity have maximum n older versions
 *
 * @author Remy Betus <remy.betus@akeneo.com>
 */
abstract class AbstractHistoryRemover
{
    /** @var  SmartManagerRegistry */
    protected $managerRegistry;

    /** @var  EntityManager */
    protected $entityManager;

    /**
     * @param SmartManagerRegistry $managerRegistry
     */
    public function __construct(SmartManagerRegistry $managerRegistry, EntityManager $entityManager)
    {
        $this->managerRegistry = $managerRegistry;
        $this->entityManager = $entityManager;
    }

    /**
     * @param OutputInterface $output
     * @param int             $months         versions created after this number of months will be deleted
     * @param int             $maximumVersion maximum number of version allowed by entities
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    abstract public function purgeHistory(OutputInterface $output, $months, $maximumVersion);
}
