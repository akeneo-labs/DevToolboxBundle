<?php

namespace Pim\Bundle\DevToolboxBundle\Updater;

use Doctrine\ORM\EntityManager;
use Pim\Bundle\CatalogBundle\Doctrine\Common\Remover\GroupRemover;
use Pim\Bundle\CatalogBundle\Manager\CompletenessManager;
use Pim\Bundle\CatalogBundle\Model\AttributeInterface;
use Pim\Bundle\CatalogBundle\Model\ChannelInterface;

/**
 * Force an attribute to be scopable
 *
 * @author    Romain Monceau <romain@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class AttributeScopabilizer
{
    /** @var EntityManager */
    protected $em;

    /** @var GroupRemover */
    protected $groupRemover;

    /** @var CompletenessManager */
    protected $completenessManager;

    /** @var CompletenessManager */
    protected $publishedCompletenessManager;

    /** @var string */
    protected $groupClass;

    /** @var string */
    protected $productValueClass;

    /** @var string */
    protected $publishedValueClass;

    /**
     * @param EntityManager $em
     */
    public function __construct(
        EntityManager $em,
        GroupRemover $groupRemover,
        CompletenessManager $completenessManager,
        CompletenessManager $publishedCompletenessManager,
        $groupClass,
        $productValueClass,
        $publishedValueClass
    ) {
        $this->em = $em;
        $this->groupRemover = $groupRemover;
        $this->completenessManager = $completenessManager;
        $this->publishedCompletenessManager = $publishedCompletenessManager;
        $this->groupClass = $groupClass;
        $this->productValueClass = $productValueClass;
        $this->publishedValueClass = $publishedValueClass;
    }

    /**
     * @param AttributeInterface $attribute
     * @param ChannelInterface   $channel
     */
    public function scopabilize(AttributeInterface $attribute, ChannelInterface $channel)
    {
        if ('pim_catalog_identifier' === $attribute->getAttributeType()) {
            throw new \Exception(
                sprintf('Identifier attribute "%s" can not be scopable', $attribute->getCode())
            );
        }

        $this->handleVariantGroups($attribute);

        $this->addProductValuesScope($attribute, $channel);

        $this->addPublishedValuesScope($attribute, $channel);

        $this->rescheduleCompleteness($channel);

    }

    /**
     * @param AttributeInterface $attribute
     */
    protected function handleVariantGroups(AttributeInterface $attribute)
    {
        $variants = $this->getGroupRepository()->getVariantGroupsByAttributeIds([$attribute->getId()]);

        foreach ($variants as $variant) {
            /** @var Group $variant */
            $variant->removeAxisAttribute($attribute);

            if (0 === count($variant->getAxisAttributes())) {
                $this->groupRemover->remove($variant, ['flush' => false]);
            }
        }
    }

    /**
     * @param AttributeInterface $attribute
     * @param ChannelInterface   $channel
     */
    protected function addProductValuesScope(AttributeInterface $attribute, ChannelInterface $channel)
    {
        $pvMetadata = $this->em->getClassMetadata($this->productValueClass);

        $dbal = $this->em->getConnection();
        $dbal->update(
            $pvMetadata->getTableName(),
            ['scope_code' => $channel->getCode()],
            ['attribute_id' => $attribute->getId()]
        );
    }

    /**
     * @param AttributeInterface $attribute
     * @param ChannelInterface   $channel
     */
    protected function addPublishedValuesScope(AttributeInterface $attribute, ChannelInterface $channel)
    {
        $ppvMetadata = $this->em->getClassMetadata($this->publishedValueClass);

        $dbal = $this->em->getConnection();
        $dbal->update(
            $ppvMetadata->getTableName(),
            ['scope_code' => $channel->getCode()],
            ['attribute_id' => $attribute->getId()]
        );
    }

    /**
     * @param ChannelInterface $channel
     */
    protected function rescheduleCompleteness(ChannelInterface $channel)
    {
        $this->completenessManager->scheduleForChannel($channel);
        $this->publishedCompletenessManager->scheduleForChannel($channel);
    }

    /**
     * @return GroupRepository
     */
    protected function getGroupRepository()
    {
        return $this->em->getRepository($this->groupClass);
    }
}
