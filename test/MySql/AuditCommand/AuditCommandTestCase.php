<?php
declare(strict_types=1);

namespace SetBased\Audit\Test\MySql\AuditCommand;

use SetBased\Audit\Command\AuditCommand;
use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Audit\Test\MySql\AuditTestCase;
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
  protected static string $dir;

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
   *
   * @return string The output of the Audit command.
   */
  protected function runAudit(int $statusCode = 0, bool $rewriteConfigFile = false): string
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

    return $commandTester->getDisplay();
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
