<?php

namespace SetBased\Audit\MySql;

use SetBased\Audit\MySql\Metadata\TableColumnsMetadata;
use SetBased\Audit\MySql\Sql\AlterAuditTableAddColumns;
use SetBased\Audit\MySql\Sql\CreateAuditTable;
use SetBased\Audit\MySql\Sql\CreateAuditTrigger;
use SetBased\Helper\CodeStore\MySqlCompoundSyntaxCodeStore;
use SetBased\Stratum\MySql\StaticDataLayer;
use SetBased\Stratum\Style\StratumStyle;

/**
 * Class for executing SQL statements and retrieving metadata from MySQL.
 */
class AuditDataLayer extends StaticDataLayer
{
  //--------------------------------------------------------------------------------------------------------------------
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
    $helper = new AlterAuditTableAddColumns($auditSchemaName, $tableName, $columns);
    $sql    = $helper->buildStatement();

    self::executeNone($sql);
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
                                            $triggerName,
                                            $triggerAction,
                                            $auditColumns,
                                            $tableColumns,
                                            $skipVariable,
                                            $additionSql)
  {
    $helper = new CreateAuditTrigger($dataSchemaName,
                                     $auditSchemaName,
                                     $tableName,
                                     $triggerName,
                                     $triggerAction,
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
    $sql->append(sprintf('create table `%s`.`%s` (', $schemaName, $tableName));
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
   * Drop table.
   *
   * @param string $schemaName The name of the table schema.
   * @param string $tableName  The name of the table.
   */
  public static function dropTemporaryTable($schemaName, $tableName)
  {
    $sql = sprintf('drop table `%s`.`%s`', $schemaName, $tableName);

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
   * @inheritdoc
   */
  public static function executeBulk($bulkHandler, $query)
  {
    self::logQuery($query);

    parent::executeBulk($bulkHandler, $query);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  public static function executeNone($query)
  {
    self::logQuery($query);

    return parent::executeNone($query);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  public static function executeRow0($query)
  {
    self::logQuery($query);

    return parent::executeRow0($query);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  public static function executeRow1($query)
  {
    self::logQuery($query);

    return parent::executeRow1($query);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  public static function executeRows($query)
  {
    self::logQuery($query);

    return parent::executeRows($query);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  public static function executeSingleton0($query)
  {
    self::logQuery($query);

    return parent::executeSingleton0($query);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  public static function executeSingleton1($query)
  {
    self::logQuery($query);

    return parent::executeSingleton1($query);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  public static function executeTable($query)
  {
    self::logQuery($query);

    return parent::executeTable($query);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects metadata of all columns of table.
   *
   * @param string $schemaName The name of the table schema.
   * @param string $tableName  The name of the table.
   *
   * @return array[]
   */
  public static function getTableColumns($schemaName, $tableName)
  {
    // When a column has no default prior to MariaDB 10.2.7 column_default is null from MariaDB 10.2.7
    // column_default = 'NULL' (string(4)).
    $sql = sprintf("
select COLUMN_NAME                    as column_name
,      COLUMN_TYPE                    as column_type
,      ifnull(COLUMN_DEFAULT, 'NULL') as column_default 
,      IS_NULLABLE                    as is_nullable
,      CHARACTER_SET_NAME             as character_set_name
,      COLLATION_NAME                 as collation_name
from   information_schema.COLUMNS
where  TABLE_SCHEMA = %s
and    TABLE_NAME   = %s
order by ORDINAL_POSITION",
                   static::quoteString($schemaName),
                   static::quoteString($tableName));

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
SELECT t1.TABLE_SCHEMA       as table_schema
,      t1.TABLE_NAME         as table_name
,      t1.TABLE_COLLATION    as table_collation
,      t1.ENGINE             as engine
,      t2.CHARACTER_SET_NAME as character_set_name
FROM       information_schema.TABLES                                t1
inner join information_schema.COLLATION_CHARACTER_SET_APPLICABILITY t2  on  t2.COLLATION_NAME = t1.TABLE_COLLATION
WHERE t1.TABLE_SCHEMA = %s
AND   t1.TABLE_NAME   = %s',
                   static::quoteString($schemaName),
                   static::quoteString($tableName));

    return self::executeRow1($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects all triggers on a table.
   *
   * @param string $schemaName The name of the table schema.
   * @param string $tableName  The name of the table.
   *
   * @return array[]
   */
  public static function getTableTriggers($schemaName, $tableName)
  {
    $sql = sprintf('
select TRIGGER_NAME as trigger_name
from   information_schema.TRIGGERS
where  TRIGGER_SCHEMA     = %s
and    EVENT_OBJECT_TABLE = %s
order by Trigger_Name',
                   static::quoteString($schemaName),
                   static::quoteString($tableName));

    return self::executeRows($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects all table names in a schema.
   *
   * @param string $schemaName The name of the schema.
   *
   * @return array[]
   */
  public static function getTablesNames($schemaName)
  {
    $sql = sprintf("
select TABLE_NAME as table_name
from   information_schema.TABLES
where  TABLE_SCHEMA = %s
and    TABLE_TYPE   = 'BASE TABLE'
order by TABLE_NAME", static::quoteString($schemaName));

    return self::executeRows($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects all triggers in a schema
   *
   * @param string $schemaName The name of the table schema.
   *
   * @return array[]
   */
  public static function getTriggers($schemaName)
  {
    $sql = sprintf('
select EVENT_OBJECT_TABLE as table_name
,      TRIGGER_NAME       as trigger_name
from   information_schema.TRIGGERS
where  TRIGGER_SCHEMA     = %s
order by EVENT_OBJECT_TABLE
,        TRIGGER_NAME',
                   static::quoteString($schemaName));

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
   * @inheritdoc
   */
  public static function multiQuery($queries)
  {
    self::logQuery($queries);

    return parent::multiQuery($queries);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  public static function query($query)
  {
    self::logQuery($query);

    return parent::query($query);
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
   * @return array[]
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
   * @inheritdoc
   */
  protected static function realQuery($query)
  {
    self::logQuery($query);

    parent::realQuery($query);
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
