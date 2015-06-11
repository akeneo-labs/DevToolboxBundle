<?php

namespace Pim\Bundle\DevToolboxBundle\Command;

use Doctrine\ORM\EntityNotFoundException;
use Pim\Bundle\CatalogBundle\Model\AttributeInterface;
use Pim\Bundle\DevToolboxBundle\Updater\AttributeScopabilizer;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Set an attribute scopable
 *
 * @author    Romain Monceau <romain@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class AttributeSetScopableCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pim:dev-toolbox:attribute:set-scopable')
            ->setDescription('Redefine an attribute as scopable')
            ->addOption('scope', null, InputOption::VALUE_REQUIRED, 'Specify the default channel code for your values')
            ->addOption('attribute', null, InputOption::VALUE_REQUIRED, 'Specify your attribute code')
        ;

    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $attributeCode = $input->getOption('attribute');
        $channelCode   = $input->getOption('scope');

        try {
            $channel = $this->getChannel($channelCode);
            $attribute = $this->getAttribute($attributeCode);

            $this->getAttributeScopabilizer()->scopabilize($attribute, $channel);

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
     * @param string $channelCode
     *
     * @return ChannelInterface
     *
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    protected function getChannel($channelCode)
    {
        $channel = $this->getChannelRepository()->findOneBy(['code' => $channelCode]);
        if (null === $channel) {
            throw new EntityNotFoundException(
                sprintf('Channel "%s" not found', $channelCode)
            );
        }

        return $channel;
    }

    /**
     * @return EntityRepository
     */
    protected function getChannelRepository()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $channelClass = $this->getContainer()->getParameter('pim_catalog.entity.channel.class');

        return $em->getRepository($channelClass);
    }

    /**
     * @return AttributeScopabilizer
     */
    protected function getAttributeScopabilizer()
    {
        return $this->getContainer()->get('pim_devtoolbox.updater.attribute_scopabilizer');
    }
}
