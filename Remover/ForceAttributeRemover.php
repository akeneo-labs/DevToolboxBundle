<?php

namespace Pim\Bundle\DevToolboxBundle\Remover;

use Doctrine\ORM\EntityManager;
use Pim\Bundle\CatalogBundle\Doctrine\Common\Remover\AttributeRemover;
use Pim\Bundle\CatalogBundle\Doctrine\Common\Remover\GroupRemover;
use Pim\Bundle\CatalogBundle\Entity\Group;
use Pim\Bundle\CatalogBundle\Entity\Repository\GroupRepository;
use Pim\Bundle\CatalogBundle\Manager\CompletenessManager;
use Pim\Bundle\CatalogBundle\Model\AttributeInterface;
use Pim\Bundle\EnrichBundle\Exception\DeleteException;

/**
 * An attribute should not be remove if it
 * - is an identifier,
 * - belongs to a variant group
 * - belongs to a published product
 *
 * @author Romain Monceau <romain@akeneo.com>
 */
class ForceAttributeRemover
{
    /** @var EntityManager */
    protected $em;

    /** @var GroupRemover */
    protected $groupRemover;

    /** @var AttributeRemover */
    protected $attributeRemover;

    /** @var CompletenessManager */
    protected $completenessManager;

    /** @var string */
    protected $groupClass;

    /** @var string */
    protected $publishedValueClass;

    /**
     * @param EntityManager $em
     * @param AttributeRemover $attributeRemover
     * @param GroupRemover  $groupRemover
     * @param CompletenessManager $completenessManager
     * @param string        $groupClass
     * @param string        $publishedValueClass
     */
    public function __construct(
        EntityManager $em,
        AttributeRemover $attributeRemover,
        GroupRemover $groupRemover,
        CompletenessManager $completenessManager, // should be completeness manager for published products
        $groupClass,
        $publishedValueClass
    ) {
        $this->em = $em;
        $this->attributeRemover = $attributeRemover;
        $this->groupRemover = $groupRemover;
        $this->completenessManager = $completenessManager;
        $this->groupClass = $groupClass;
        $this->publishedValueClass = $publishedValueClass;
    }

    /**
     * @param AttributeInterface $attribute
     *
     * TODO: Check with each kind of attribute type
     * -> option: check axis on variant
     * -> medias: delete files
     * -> prices: linked entity
     * -> metric: linked entity
     * -> options: join table
     */
    public function remove(AttributeInterface $attribute)
    {
        // Check that this attribute is not the SKU
        if ('pim_catalog_identifier' === $attribute->getAttributeType()) {
            throw new DeleteException(
                sprintf('Identifier attribute "%s" can not be removed', $attribute->getCode())
            );
        }

        $this->handleVariantGroups($attribute);

        // Only for EE
        $this->deletePublishedProductValues($attribute);
        $this->rescheduleCompleteness($attribute);
        // End of EE

        $this->attributeRemover->remove($attribute); // flush is done here!
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
     */
    protected function deletePublishedProductValues(AttributeInterface $attribute)
    {
        $ppvMetadata = $this->em->getClassMetadata($this->publishedValueClass);

        $dbal = $this->em->getConnection();
        $dbal->delete($ppvMetadata->getTableName(), ['attribute_id' => $attribute->getId()]);
    }

    /**
     * @param AttributeInterface $attribute
     */
    protected function rescheduleCompleteness(AttributeInterface $attribute)
    {
        foreach ($attribute->getFamilies() as $family) {
            $this->completenessManager->scheduleForFamily($family);
        }
    }

    /**
     * @return GroupRepository
     */
    protected function getGroupRepository()
    {
        return $this->em->getRepository($this->groupClass);
    }
}
