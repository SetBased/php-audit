<?php

namespace SetBased\Audit\Test\MySql;

use PHPUnit\Framework\TestCase;
use SetBased\Stratum\MySql\StaticDataLayer;

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
  protected static $auditSchema = 'test_audit';

  /**
   * The schema name with the data (or application's) tables.
   *
   * @var string
   */
  protected static $dataSchema = 'test_data';

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Connects to the MySQL server.
   */
  public static function setUpBeforeClass()
  {
    parent::setUpBeforeClass();

    StaticDataLayer::connect('localhost', 'test', 'test', self::$dataSchema);

    self::dropAllTables();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Drops all tables in test_data and test_audit.
   */
  protected static function dropAllTables()
  {
    $sql = "
select TABLE_SCHEMA as table_schema
,      TABLE_NAME   as table_name
from   information_schema.TABLES
where TABLE_SCHEMA in (%s,%s)";

    $sql = sprintf($sql,
                   StaticDataLayer::quoteString(self::$dataSchema),
                   StaticDataLayer::quoteString(self::$auditSchema));

    $tables = StaticDataLayer::executeRows($sql);

    foreach ($tables as $table)
    {
      $sql = "drop table `%s`.`%s`";
      $sql = sprintf($sql, $table['table_schema'], $table['table_name']);

      StaticDataLayer::executeNone($sql);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Connects to MySQL instance.
   */
  protected function setUp()
  {
    StaticDataLayer::disconnect();
    StaticDataLayer::connect('localhost', 'test', 'test', self::$dataSchema);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Disconnects from MySQL instance.
   */
  protected function tearDown()
  {
    StaticDataLayer::disconnect();
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
