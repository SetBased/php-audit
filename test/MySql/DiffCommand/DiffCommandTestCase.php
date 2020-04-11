<?php
declare(strict_types=1);

namespace SetBased\Audit\Test\MySql\DiffCommand;

use SetBased\Audit\Command\AuditCommand;
use SetBased\Audit\Command\DiffCommand;
use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Audit\Test\MySql\AuditTestCase;
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
  public static function setUpBeforeClass(): void
  {
    parent::setUpBeforeClass();

    AuditDataLayer::$dl->disconnect();
    AuditDataLayer::$dl->connect();

    AuditDataLayer::$dl->executeMulti(file_get_contents(self::$dir.'/config/setup.sql'));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Runs the audit command, i.e. creates the audit table.
   *
   * @param int  $statusCode        The expected status code of the command.
   * @param bool $rewriteConfigFile If true the config file will be rewritten.
   */
  protected function runAudit(int $statusCode = 0, bool $rewriteConfigFile = false): void
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
    AuditDataLayer::$dl->connect();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Runs the diff command and returns the output of the diff command.
   *
   * @param bool $full If true the --full option will be set.
   *
   * @return string
   */
  protected function runDiff(bool $full = false): string
  {
    $application = new Application();
    $application->add(new DiffCommand());

    /** @var DiffCommand $command */
    $command = $application->find('diff');
    $command->setRewriteConfigFile(false);
    $commandTester = new CommandTester($command);
    $commandTester->execute(['command'     => $command->getName(),
                             '--full'      => $full,
                             'config file' => self::$dir.'/config/audit.json']);

    return $commandTester->getDisplay();
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
