<?php

namespace Pim\Bundle\DevToolboxBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Purge history of products
 *
 * @author    Remy Betus <remy.betus@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class HistoryPurgeCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pim:dev-toolbox:purge-history')
            ->setDescription('Purge history of entities')
            ->addOption(
                'months',
                'mth',
                InputOption::VALUE_OPTIONAL,
                'Set the number of months beyond which versions of entity will be removed.'
            )
            ->addOption(
                'max-version',
                'mxv',
                InputOption::VALUE_OPTIONAL,
                'Set the maximum number of version an entity can have, others will be removed.'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $months = $input->getOption('months');
        $maximumVersion = $input->getOption('max-version');
        $dialog = $this->getHelper('dialog');

        if (!$months) {
            $months = $dialog->askAndValidate(
                $output,
                "<info>Please enter the number of months beyond which versions of entities will be removed.</info>\n<comment>Default: 3, use 0 to ignore</comment>: ",
                function ($answer) {
                    if (!ctype_digit($answer)) {
                        throw new \RuntimeException(
                            'The number of months must be an integer.'
                        );
                    }

                    return $answer;
                },
                false,
                '3'
            );
        }
        if ($months > 0) {
            $output->writeln("<comment>Versions older than</comment> <info>$months</info> <comment>months will be removed.</comment>\n");
        } else {
            $output->writeln("No number of months set, option ignored.\n");
        }

        if (!$maximumVersion) {
            $maximumVersion = $dialog->askAndValidate(
                $output,
                "<info>Please enter the maximum number of version an entity can have, others will be removed.</info>\n<comment>Default: 1, use 0 to ignore</comment>: ",
                function ($answer) {
                    if (!ctype_digit($answer)) {
                        throw new \RuntimeException(
                            'The number of months must be an integer.'
                        );
                    }

                    return $answer;
                },
                false,
                '1'
            );
        }

        if ($maximumVersion > 0) {
            $output->writeln("<comment>A maximum of </comment><info>$maximumVersion</info><comment> versions of each entity will be kept, others will be removed.</comment>\n");
        } else {
            $output->writeln("No number of months set, option ignored.\n");
        }

        if (0 == $maximumVersion && 0 == $months) {
            $output->writeln("<error>No parameters sex²t.</error>");
        }

        $this->getContainer()->get('pim_dev_toolbox.remover.history_remover')->purgeHistory(
            $output,
            $months,
            $maximumVersion
        );
    }


}
