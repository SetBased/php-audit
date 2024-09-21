<?php
declare(strict_types=1);

namespace SetBased\Audit\Test\MySql\AlterAuditTableCommand;

use SetBased\Audit\Command\AlterAuditTableCommand;
use SetBased\Audit\Command\AuditCommand;
use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Audit\Test\MySql\AuditTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Parent class for testing the diff command.
 */
class AlterAuditTableCommandTest extends AuditTestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  public function setUp(): void
  {
    parent::setUp();

    $this->dropAllTables();

    AuditDataLayer::$dl->executeMulti(file_get_contents(__DIR__.'/'.$this->name().'/setup.sql'));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test default character set has changed.
   */
  public function testCharset(): void
  {
    $this->base('testCharset');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test default collation has changed.
   */
  public function testCollation(): void
  {
    $this->base('testCollation');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test engine has changed,
   */
  public function testEngine(): void
  {
    $this->base('testEngine');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test from 'int(11)' to 'int(8)'.
   */
  public function testIntColumn(): void
  {
    $this->base('testIntColumn');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test with many changes.
   */
  public function testMany(): void
  {
    $this->base('testMany');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test multiple columns changed.
   */
  public function testMultipleColumns(): void
  {
    $this->base('testMultipleColumns');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test from 'datetime' to 'timestamp'.
   */
  public function testTimestampColumn(): void
  {
    $this->base('testTimestampColumn');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test from 'varchar(10) utf8' to 'varchar(10) ascii collate ascii_bin' (same length different charset and collate).
   */
  public function testVarcharColumn(): void
  {
    $this->base('testVarcharColumn');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Runs the alter-audit-table command, i.e. creates the alter table sql statement.
   *
   * @param int  $statusCode        The expected status code of the command.
   * @param bool $rewriteConfigFile If true the config file will be rewritten.
   */
  protected function runAlter(int $statusCode = 0, bool $rewriteConfigFile = false): void
  {
    $application = new Application();
    $application->add(new AlterAuditTableCommand());

    /** @var AlterAuditTableCommand $command */
    $command = $application->find('alter-audit-table');
    $command->setRewriteConfigFile($rewriteConfigFile);
    $commandTester = new CommandTester($command);
    $commandTester->execute(['command'     => $command->getName(),
                             'config file' => __DIR__.'/config/audit.json',
                             'sql file'    => __DIR__.'/config/alter.sql']);

    $this->assertSame($statusCode, $commandTester->getStatusCode(), 'status_code');

    // Reconnects to the MySQL instance (because the audit command always disconnects from the MySQL instance).
    AuditDataLayer::$dl->connect();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Runs the audit command, i.e. creates the audit table.
   */
  protected function runAudit(): void
  {
    $application = new Application();
    $application->add(new AuditCommand());

    /** @var AuditCommand $command */
    $command = $application->find('audit');
    $command->setRewriteConfigFile(true);
    $commandTester = new CommandTester($command);
    $commandTester->execute(['command'     => $command->getName(),
                             'config file' => __DIR__.'/config/audit.json']);

    // Reconnects to the MySQL instance (because the audit command always disconnects from the MySQL instance).
    AuditDataLayer::$dl->connect();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The base method for testing a change of a single column.
   *
   * @param string $name The name of the test.
   */
  private function base(string $name): void
  {
    // Run audit.
    $this->runAudit();

    // Alter table(s) in the data schema.
    AuditDataLayer::$dl->executeMulti(file_get_contents(__DIR__.'/'.$name.'/alter.sql'));

    $this->runAlter();

    $alter = trim(file_get_contents(__DIR__.'/config/alter.sql'));

    // Fix for MariaDB 10.6+
    $alter = str_replace('utf8mb3', 'utf8', $alter);

    // Fix for MySQL 8.x.
    $alter = str_replace('bigint ', 'bigint(20) ', $alter);

    // Compare the generated and expected SQL.
    $this->assertEquals(trim(file_get_contents(__DIR__.'/'.$name.'/expected.sql')), $alter);

    // Run the alter script.
    AuditDataLayer::$dl->executeMulti(file_get_contents(__DIR__.'/config/alter.sql'));

    // Rerun the alter-audit-table command.
    $this->runAlter();

    $this->assertEquals('', file_get_contents(__DIR__.'/config/alter.sql'));
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
