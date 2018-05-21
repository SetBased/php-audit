<?php

namespace SetBased\Audit\Test\MySql\AuditCommand;

use SetBased\Audit\MySql\Command\AuditCommand;
use SetBased\Audit\Test\MySql\AuditTestCase;
use SetBased\Stratum\MySql\StaticDataLayer;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests changed character set of a column.
 */
class AuditCommandTestCase extends AuditTestCase
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
   *
   * @param int  $statusCode        The expected status code of the command.
   * @param bool $rewriteConfigFile If true the config file will be rewritten.
   */
  protected function runAudit($statusCode = 0, $rewriteConfigFile = false)
  {
    $application = new Application();
    $application->add(new AuditCommand());

    /** @var AuditCommand $command */
    $command = $application->find('audit');
    $command->setRewriteConfigFile($rewriteConfigFile);
    $commandTester = new CommandTester($command);
    $commandTester->execute(['command'     => $command->getName(),
                             'config file' => self::$dir.'/config/audit.json']);

    self::assertSame($statusCode, $commandTester->getStatusCode(), 'status_code');

    // Reconnects to the MySQL instance (because the audit command always disconnects from the MySQL instance).
    StaticDataLayer::connect('localhost', 'test', 'test', self::$dataSchema);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
