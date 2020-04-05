<?php declare(strict_types=1);

namespace Spacetab\Configuration\Console;

use Spacetab\Configuration\Configuration;
use Spacetab\Logger\Logger;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DumpConfigCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('dump')
            ->addArgument('path', InputArgument::OPTIONAL, 'Configuration directory path')
            ->addArgument('stage', InputArgument::OPTIONAL, 'Configuration $STAGE')
            ->addOption('inline', 'l', InputOption::VALUE_OPTIONAL, 'The level where you switch to inline YAML', 10)
            ->addOption('indent', 's', InputOption::VALUE_OPTIONAL, 'The amount of spaces to use for indentation of nested nodes', 2)
            ->addOption('debug', 'd', InputOption::VALUE_NONE, 'Debug')
            ->setDescription('Dump loaded configuration')
            ->setHelp('Example of usage: `st-conf dump`. Options --inline=10 (nesting level) and --indent=2. If [path] and [stage] arguments not passed will be used global env variables CONFIG_PATH and STAGE.');
    }

    /**
     * Execute command, captain.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $conf = new Configuration(
            $input->getArgument('path'),
            $input->getArgument('stage')
        );

        if ($input->getOption('debug')) {
            $conf->setLogger(Logger::default('Conf', LogLevel::DEBUG));
        }

        $conf->load();

        $string = $conf->dump(
            (int) $input->getOption('inline'),
            (int) $input->getOption('indent')
        );

        $output->writeln($string);
    }
}
