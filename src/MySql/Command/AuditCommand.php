<?php

namespace SetBased\Audit\MySql\Command;

use SetBased\Audit\MySql\Audit;
use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Stratum\Style\StratumStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for creating audit tables and audit triggers.
 */
class AuditCommand extends MySqlBaseCommand
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this->setName('audit')
         ->setDescription('Maintains audit tables and audit triggers')
         ->setHelp("Maintains audit tables and audit triggers:\n".
                   "- creates new audit tables\n".
                   "- adds new columns to exiting audit tables\n".
                   "- creates new and recreates existing audit triggers\n")
         ->addArgument('config file', InputArgument::REQUIRED, 'The audit configuration file');
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

    // Create database connection with params from config file
    $this->connect($this->config);

    $audit  = new Audit($this->config, $this->configMetadata, $this->io);
    $status = $audit->main();

    AuditDataLayer::disconnect();

    $this->rewriteConfig();

    return $status;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
