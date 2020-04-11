<?php
declare(strict_types=1);

namespace SetBased\Audit\Command;

use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Audit\Style\AuditStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for dropping all triggers.
 */
class DropTriggersCommand extends AuditCommand
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function configure()
  {
    $this->setName('drop-triggers')
         ->setDescription('Drops all triggers')
         ->setHelp('Drops all triggers (including triggers not created by audit) from all tables (including tables '.
                   'excluded for auditing) in the data schema.')
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

    $this->dropTriggers();

    AuditDataLayer::$dl->disconnect();

    $this->rewriteConfig();

    return 0;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Drops all triggers.
   */
  private function dropTriggers(): void
  {
    $dataSchema = $this->config->getManString('database.data_schema');
    $triggers   = AuditDataLayer::$dl->getTriggers($dataSchema);
    foreach ($triggers as $trigger)
    {
      $this->io->logInfo('Dropping trigger <dbo>%s</dbo> from table <dbo>%s</dbo>',
                         $trigger['trigger_name'],
                         $trigger['table_name']);

      AuditDataLayer::$dl->dropTrigger($dataSchema, $trigger['trigger_name']);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
