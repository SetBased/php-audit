<?php

namespace SetBased\Audit\MySql\Command;

use SetBased\Audit\MySql\AuditDiff;
use SetBased\Stratum\Style\StratumStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for comparing data tables with audit tables.
 */
class DiffCommand extends AuditCommand
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Check full full and return array without new or obsolete columns if full not set.
   *
   * @param array[] $columns The metadata of the columns of a table.
   *
   * @var StratumStyle
   */
  protected $io;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this->setName('diff')
         ->setDescription('Compares data tables and audit tables')
         ->addArgument('config file', InputArgument::REQUIRED, 'The audit configuration file')
         ->addOption('full', 'f', InputOption::VALUE_NONE, 'Show all columns');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->io = new StratumStyle($input, $output);

    $this->configFileName = $input->getArgument('config file');
    $this->readConfigFile();

    $this->connect($this->config);

    $diff = new AuditDiff($this->config, $this->configMetadata, $this->io, $input, $output);
    $diff->main();
  }

  //--------------------------------------------------------------------------------------------------------------------

}

//----------------------------------------------------------------------------------------------------------------------
