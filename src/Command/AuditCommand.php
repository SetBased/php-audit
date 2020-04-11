<?php
declare(strict_types=1);

namespace SetBased\Audit\Command;

use SetBased\Audit\Audit\Audit;
use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Audit\Style\AuditStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for creating audit tables and audit triggers.
 */
class AuditCommand extends BaseCommand
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
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
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->io = new AuditStyle($input, $output);

    $this->configFileName = $input->getArgument('config file');
    $this->readConfigFile();

    $this->connect();

    $audit = new Audit($this->config, $this->io);
    $audit->main();

    AuditDataLayer::$dl->disconnect();

    $this->rewriteConfig();

    return 0;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
