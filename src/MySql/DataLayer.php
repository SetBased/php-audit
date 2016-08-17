<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\Audit\MySql;

use SetBased\Audit\MySql\Metadata\TableColumnsMetadata;
use SetBased\Audit\MySql\Sql\AlterAuditTableAddColumns;
use SetBased\Audit\MySql\Sql\CreateAuditTable;
use SetBased\Audit\MySql\Sql\CreateAuditTrigger;
use SetBased\Helper\CodeStore\MySqlCompoundSyntaxCodeStore;
use SetBased\Stratum\MySql\StaticDataLayer;
use SetBased\Stratum\Style\StratumStyle;

//----------------------------------------------------------------------------------------------------------------------
/**
 * Class for executing SQL statements and retrieving metadata from MySQL.
 */
class DataLayer
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The connection to the MySQL instance.
   *
   * @var StaticDataLayer
   */
  private static $dl;

  /**
   * The Output decorator.
   *
   * @var StratumStyle
   */
  private static $io;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Adds new columns to an audit table.
   *
   * @param string               $auditSchemaName The name of audit schema.
   * @param string               $tableName       The name of the table.
   * @param TableColumnsMetadata $columns         The metadata of the new columns.
   */
  public static function addNewColumns($auditSchemaName, $tableName, $columns)
  {
    // Return immediately if there are no columns to add. 
    if ($columns->getNumberOfColumns()==0) return;

    $helper = new AlterAuditTableAddColumns($auditSchemaName, $tableName, $columns);
    $sql    = $helper->buildStatement();

    self::executeNone($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Connects to a MySQL instance.
   *
   * Wrapper around [mysqli::__construct](http://php.net/manual/mysqli.construct.php), however on failure an exception
   * is thrown.
   *
   * @param string $host     The hostname.
   * @param string $user     The MySQL user name.
   * @param string $passWord The password.
   * @param string $database The default database.
   * @param int    $port     The port number.
   */
  public static function connect($host, $user, $passWord, $database, $port = 3306)
  {
    self::$dl = new StaticDataLayer();

    self::$dl->connect($host, $user, $passWord, $database, $port);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Creates an audit table.
   *
   * @param string               $dataSchemaName  The name of the data schema.
   * @param string               $auditSchemaName The name of the audit schema.
   * @param string               $tableName       The name of the table.
   * @param TableColumnsMetadata $columns         The metadata of the columns of the audit table (i.e. the audit
   *                                              columns and columns of the data table).
   */
  public static function createAuditTable($dataSchemaName, $auditSchemaName, $tableName, $columns)
  {
    $helper = new CreateAuditTable($dataSchemaName, $auditSchemaName, $tableName, $columns);
    $sql    = $helper->buildStatement();

    self::executeNone($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Creates a trigger on a table.
   *
   * @param string               $dataSchemaName  The name of the data schema.
   * @param string               $auditSchemaName The name of the audit schema.
   * @param string               $tableName       The name of the table.
   * @param string               $triggerAction   The trigger action (i.e. INSERT, UPDATE, or DELETE).
   * @param string               $triggerName     The name of the trigger.
   * @param TableColumnsMetadata $auditColumns    The audit table columns.
   * @param TableColumnsMetadata $tableColumns    The data table columns.
   * @param string               $skipVariable    The skip variable.
   * @param string[]             $additionSql     Additional SQL statements.
   */
  public static function createAuditTrigger($dataSchemaName,
                                            $auditSchemaName,
                                            $tableName,
                                            $triggerAction,
                                            $triggerName,
                                            $auditColumns,
                                            $tableColumns,
                                            $skipVariable,
                                            $additionSql)
  {
    $helper = new CreateAuditTrigger($dataSchemaName,
                                     $auditSchemaName,
                                     $tableName,
                                     $triggerAction,
                                     $triggerName,
                                     $auditColumns,
                                     $tableColumns,
                                     $skipVariable,
                                     $additionSql);
    $sql    = $helper->buildStatement();

    self::executeNone($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Create temp table for getting column type information for audit columns.
   *
   * @param string  $schemaName   The name of the table schema.
   * @param string  $tableName    The table name.
   * @param array[] $auditColumns Audit columns from config file.
   */
  public static function createTemporaryTable($schemaName, $tableName, $auditColumns)
  {
    $sql = new MySqlCompoundSyntaxCodeStore();
    $sql->append(sprintf('create temporary table `%s`.`%s` (', $schemaName, $tableName));
    foreach ($auditColumns as $column)
    {
      $sql->append(sprintf('%s %s', $column['column_name'], $column['column_type']));
      if (end($auditColumns)!==$column)
      {
        $sql->appendToLastLine(',');
      }
    }
    $sql->append(')');

    self::executeNone($sql->getCode());
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Closes the connection to the MySQL instance, if connected.
   */
  public static function disconnect()
  {
    if (self::$dl!==null)
    {
      self::$dl->disconnect();
      self::$dl = null;
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Drop table.
   *
   * @param string $schemaName The name of the table schema.
   * @param string $tableName  The name of the table.
   */
  public static function dropTemporaryTable($schemaName, $tableName)
  {
    $sql = sprintf('drop temporary table `%s`.`%s`', $schemaName, $tableName);

    self::executeNone($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Drops a trigger.
   *
   * @param string $triggerSchema The name of the trigger schema.
   * @param string $triggerName   The mame of trigger.
   */
  public static function dropTrigger($triggerSchema, $triggerName)
  {
    $sql = sprintf('drop trigger `%s`.`%s`', $triggerSchema, $triggerName);

    self::executeNone($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @param string $query The SQL statement.
   *
   * @return int The number of affected rows (if any).
   */
  public static function executeNone($query)
  {
    self::logQuery($query);

    return self::$dl->executeNone($query);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Executes a query that returns 0 or 1 row.
   * Throws an exception if the query selects 2 or more rows.
   *
   * @param string $query The SQL statement.
   *
   * @return array|null The selected row.
   */
  public static function executeRow0($query)
  {
    self::logQuery($query);

    return self::$dl->executeRow0($query);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Executes a query that returns 1 and only 1 row.
   * Throws an exception if the query selects none, 2 or more rows.
   *
   * @param string $query The SQL statement.
   *
   * @return array The selected row.
   */
  public static function executeRow1($query)
  {
    self::logQuery($query);

    return self::$dl->executeRow1($query);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Executes a query that returns 0 or more rows.
   *
   * @param string $query The SQL statement.
   *
   * @return \array[]
   */
  public static function executeRows($query)
  {
    self::logQuery($query);

    return self::$dl->executeRows($query);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Executes a query that returns 0 or 1 row.
   * Throws an exception if the query selects 2 or more rows.
   *
   * @param string $query The SQL statement.
   *
   * @return int|string|null The selected row.
   */
  public static function executeSingleton0($query)
  {
    self::logQuery($query);

    return self::$dl->executeSingleton0($query);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Executes a query that returns 1 and only 1 row with 1 column.
   * Throws an exception if the query selects none, 2 or more rows.
   *
   * @param string $query The SQL statement.
   *
   * @return int|string The selected row.
   */
  public static function executeSingleton1($query)
  {
    self::logQuery($query);

    return self::$dl->executeSingleton1($query);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects metadata of all columns of table.
   *
   * @param string $schemaName The name of the table schema.
   * @param string $tableName  The name of the table.
   *
   * @return \array[]
   */
  public static function getTableColumns($schemaName, $tableName)
  {
    $sql = sprintf('
select COLUMN_NAME        as column_name
,      COLUMN_TYPE        as column_type
,      IS_NULLABLE        as is_nullable
,      CHARACTER_SET_NAME as character_set_name
,      COLLATION_NAME     as collation_name
from   information_schema.COLUMNS
where  TABLE_SCHEMA = %s
and    TABLE_NAME   = %s
order by ORDINAL_POSITION',
                   self::$dl->quoteString($schemaName),
                   self::$dl->quoteString($tableName));

    return self::executeRows($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects table engine, character_set_name and table_collation.
   *
   * @param string $schemaName The name of the table schema.
   * @param string $tableName  The name of the table.
   *
   * @return array
   */
  public static function getTableOptions($schemaName, $tableName)
  {
    $sql = sprintf('
SELECT t1.TABLE_COLLATION    as table_collation
,      t1.ENGINE             as engine
,      t2.CHARACTER_SET_NAME as character_set_name
FROM       information_schema.TABLES                                t1
inner join information_schema.COLLATION_CHARACTER_SET_APPLICABILITY t2  on  t2.COLLATION_NAME = t1.TABLE_COLLATION
WHERE t1.TABLE_SCHEMA = %s
AND   t1.TABLE_NAME   = %s',
                   self::$dl->quoteString($schemaName),
                   self::$dl->quoteString($tableName));

    return self::executeRow1($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects all triggers on a table.
   *
   * @param string $schemaName The name of the table schema.
   * @param string $tableName  The name of the table.
   *
   * @return \array[]
   */
  public static function getTableTriggers($schemaName, $tableName)
  {
    $sql = sprintf('
select Trigger_Name as trigger_name
from   information_schema.TRIGGERS
where  TRIGGER_SCHEMA     = %s
and    EVENT_OBJECT_TABLE = %s
order by Trigger_Name',
                   self::$dl->quoteString($schemaName),
                   self::$dl->quoteString($tableName));

    return self::executeRows($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects all table names in a schema.
   *
   * @param string $schemaName The name of the schema.
   *
   * @return \array[]
   */
  public static function getTablesNames($schemaName)
  {
    $sql = sprintf("
select TABLE_NAME as table_name
from   information_schema.TABLES
where  TABLE_SCHEMA = %s
and    TABLE_TYPE   = 'BASE TABLE'
order by TABLE_NAME", self::$dl->quoteString($schemaName));

    return self::executeRows($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Acquires a write lock on a table.
   *
   * @param string $tableName The table name.
   */
  public static function lockTable($tableName)
  {
    $sql = sprintf('lock tables `%s` write', $tableName);

    self::executeNone($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Sets the Output decorator.
   *
   * @param StratumStyle $io The Output decorator.
   */
  public static function setIo($io)
  {
    self::$io = $io;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Drop table.
   *
   * @param string $schemaName The name of the table schema.
   * @param string $tableName  The name of the table.
   *
   * @return \array[]
   */
  public static function showColumns($schemaName, $tableName)
  {
    $sql = sprintf('SHOW COLUMNS FROM `%s`.`%s`', $schemaName, $tableName);

    return self::executeRows($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Releases all table locks.
   */
  public static function unlockTables()
  {
    $sql = 'unlock tables';

    self::executeNone($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Logs the query on the console.
   *
   * @param string $query The query.
   */
  private static function logQuery($query)
  {
    $query = trim($query);

    if (strpos($query, "\n")!==false)
    {
      // Query is a multi line query.
      self::$io->logVeryVerbose('Executing query:');
      self::$io->logVeryVerbose('<sql>%s</sql>', $query);
    }
    else
    {
      // Query is a single line query.
      self::$io->logVeryVerbose('Executing query: <sql>%s</sql>', $query);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
