<?php

namespace Pim\Bundle\DevToolboxBundle\Command;

use Doctrine\ORM\EntityRepository;
use Pim\Bundle\CatalogBundle\Model\AttributeInterface;
use Pim\Bundle\DevToolboxBundle\Remover\ForceAttributeRemover;
use Pim\Bundle\InstallerBundle\CommandExecutor;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Force the deletion of an attribute
 *
 * @author    Romain Monceau <romain@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class AttributeDeleteCommand extends ContainerAwareCommand
{
    /** @var CommandExecutor */
    protected $commandExecutor;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pim:dev-toolbox:attribute:delete')
            ->setDescription('Delete an attribute')
            ->addOption(
                'attribute',
                null,
                InputOption::VALUE_REQUIRED,
                'Specify your attribute code'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $attributeCode = $input->getOption('attribute');

        try {
            $attribute = $this->getAttribute($attributeCode);

            $output->writeln(sprintf('<info>Attribute "%s" removing...</info>', $attributeCode));

            $this->getAttributeRemover()->remove($attribute);

            $output->writeln(sprintf('<info>Attribute "%s" successfully removed</info>', $attributeCode));
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
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
            throw new \Exception(
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
}
