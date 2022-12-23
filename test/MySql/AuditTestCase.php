<?php
declare(strict_types=1);

namespace SetBased\Audit\Test\MySql;

use PHPUnit\Framework\TestCase;
use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Audit\Style\AuditStyle;
use SetBased\Stratum\MySql\MySqlDefaultConnector;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Parent class for the Audit test classes.
 */
class AuditTestCase extends TestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The schema name with the audit tables.
   *
   * @var string
   */
  protected static string $auditSchema = 'test_audit';

  /**
   * The schema name with the data (or application's) tables.
   *
   * @var string
   */
  protected static string $dataSchema = 'test_data';

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Connects to the MySQL server.
   */
  public static function setUpBeforeClass(): void
  {
    parent::setUpBeforeClass();

    $connector = new MySqlDefaultConnector('127.0.0.1', 'test', 'test', self::$dataSchema);
    $io        = new AuditStyle(new ArgvInput(), new ConsoleOutput());
    $dl        = new AuditDataLayer($connector, $io);
    $dl->connect();

    self::dropAllTables();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Drops all tables in test_data and test_audit.
   */
  protected static function dropAllTables(): void
  {
    $sql = "
select TABLE_SCHEMA as table_schema
,      TABLE_NAME   as table_name
from   information_schema.TABLES
where TABLE_SCHEMA in (%s,%s)";

    $sql = sprintf($sql,
                   AuditDataLayer::$dl->quoteString(self::$dataSchema),
                   AuditDataLayer::$dl->quoteString(self::$auditSchema));

    $tables = AuditDataLayer::$dl->executeRows($sql);

    foreach ($tables as $table)
    {
      $sql = "drop table `%s`.`%s`";
      $sql = sprintf($sql, $table['table_schema'], $table['table_name']);

      AuditDataLayer::$dl->executeNone($sql);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Connects to MySQL instance.
   */
  protected function setUp(): void
  {
    AuditDataLayer::$dl->disconnect();
    AuditDataLayer::$dl->connect();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Disconnects from MySQL instance.
   */
  protected function tearDown(): void
  {
    AuditDataLayer::$dl->disconnect();
    AuditDataLayer::$dl->connect();
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
