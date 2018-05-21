<?php

namespace SetBased\Audit\Test\MySql\DiffCommand;

use SetBased\Audit\MySql\Command\AuditCommand;
use SetBased\Audit\MySql\Command\DiffCommand;
use SetBased\Audit\Test\MySql\AuditTestCase;
use SetBased\Stratum\MySql\StaticDataLayer;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Parent class for testing the diff command.
 */
class DiffCommandTestCase extends AuditTestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The directory of the test case.
   *
   * @var string
   */
  protected static $dir;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  public static function setUpBeforeClass()
  {
    parent::setUpBeforeClass();

    StaticDataLayer::disconnect();
    StaticDataLayer::connect('localhost', 'test', 'test', self::$dataSchema);

    StaticDataLayer::multiQuery(file_get_contents(self::$dir.'/config/setup.sql'));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Runs the audit command, i.e. creates the audit table.
   */
  protected function runAudit()
  {
    $application = new Application();
    $application->add(new AuditCommand());

    /** @var AuditCommand $command */
    $command = $application->find('audit');
    $command->setRewriteConfigFile(true);
    $commandTester = new CommandTester($command);
    $commandTester->execute(['command'     => $command->getName(),
                             'config file' => self::$dir.'/config/audit.json']);

    // Reconnects to the MySQL instance (because the audit command always disconnects from the MySQL instance).
    StaticDataLayer::connect('localhost', 'test', 'test', self::$dataSchema);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Runs the diff command and returns the output of the diff command.
   *
   * @return string
   */
  protected function runDiff()
  {
    $application = new Application();
    $application->add(new DiffCommand());

    /** @var DiffCommand $command */
    $command = $application->find('diff');
    $command->setRewriteConfigFile(false);
    $commandTester = new CommandTester($command);
    $commandTester->execute(['command'     => $command->getName(),
                             'config file' => self::$dir.'/config/audit.json']);

    return $commandTester->getDisplay();
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
