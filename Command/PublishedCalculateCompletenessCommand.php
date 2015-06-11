<?php

namespace Pim\Bundle\DevToolboxBundle\Command;

use Pim\Bundle\DevToolboxBundle\Doctrine\ORM\PublishedCompletenessGenerator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 * @author Romain Monceau <romain@akeneo.com>
 */
class PublishedCalculateCompletenessCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pim:published_completeness:calculate')
            ->setDescription('Launch the product completeness calculation');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("<info>Generating missing completenesses...</info>");
        $this->getCompletenessGenerator()->generateMissing();
        $output->writeln("<info>Missing completenesses generated.</info>");
    }

    /**
     * @return PublishedCompletenessGenerator
     */
    protected function getCompletenessGenerator()
    {
        return $this
            ->getContainer()
            ->get('pim_devtoolbox.doctrine.published_completeness_generator');
    }
}
