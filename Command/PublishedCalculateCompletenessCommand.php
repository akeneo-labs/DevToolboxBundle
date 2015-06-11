<?php

namespace Pim\Bundle\DevToolboxBundle\Command;

use Pim\Bundle\DevToolboxBundle\Doctrine\ORM\PublishedCompletenessGenerator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command launch the completeness on published products
 *
 * @author    Romain Monceau <romain@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class PublishedCalculateCompletenessCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pim:devtoolbox:published_completeness:calculate')
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
