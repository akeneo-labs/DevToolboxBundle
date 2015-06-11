<?php

namespace Pim\Bundle\DevToolboxBundle\Command;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\EntityRepository;
use Pim\Bundle\CatalogBundle\Manager\CompletenessManager;
use Pim\Bundle\CatalogBundle\Model\AttributeInterface;
use Pim\Bundle\DevToolboxBundle\Remover\ForceAttributeRemover;
use Pim\Bundle\InstallerBundle\CommandExecutor;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Force the deletion of an attribute
 *
 * @author Romain Monceau <romain@akeneo.com>
 */
class AttributeUpdateCommand extends ContainerAwareCommand
{
    /** @var CommandExecutor */
    protected $commandExecutor;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pim:dev-toolbox:delete-attribute')
            ->setDescription('Delete an attribute')
            ->addArgument('attributes', InputArgument::IS_ARRAY, 'Specify your attribute codes');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $attributeCodes = $input->getArgument('attributes');

        foreach ($attributeCodes as $attributeCode) {
            $attribute = $this->getAttribute($attributeCode); // TODO: Display errors if not found

            $this->getAttributeRemover()->remove($attribute); //TODO: Display result or errors
        }
    }

    /**
     * @param string $attributeCode
     *
     * @return AttributeInterface
     *
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    protected function getAttribute($attributeCode)
    {
        $attribute = $this->getAttributeRepository()->findOneBy(['code' => $attributeCode]);
        if (null === $attribute) {
            throw new EntityNotFoundException(
                sprintf('Attribute "%s" not found', $attributeCode)
            );
        }

        return $attribute;
    }

    /**
     * @return EntityRepository
     */
    protected function getAttributeRepository()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $attributeClass = $this->getContainer()->getParameter('pim_catalog.entity.attribute.class');

        return $em->getRepository($attributeClass);
    }

    /**
     * @return ForceAttributeRemover
     */
    protected function getAttributeRemover()
    {
        return $this->getContainer()->get('pim_devtoolbox.remover.force_attribute');
    }

    /**
     * @return CompletenessManager
     */
    protected function getCompletenessManager()
    {
        return $this->getContainer()->get('pim_catalog.manager.completeness');
    }

    /**
     * @return CompletenessManager
     */
    protected function getPublishedCompletenessManager()
    {
        return $this->getContainer()->get('pim_devtoolbox.manager.published_completeness');
    }
}
