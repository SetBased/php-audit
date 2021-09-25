<?php
declare(strict_types=1);

namespace SetBased\Audit\MySql;

use SetBased\Audit\Metadata\TableColumnsMetadata;
use SetBased\Audit\MySql\Sql\AlterAuditTableAddColumns;
use SetBased\Audit\MySql\Sql\CreateAuditTable;
use SetBased\Audit\MySql\Sql\CreateAuditTrigger;
use SetBased\Audit\Style\AuditStyle;
use SetBased\Helper\CodeStore\MySqlCompoundSyntaxCodeStore;
use SetBased\Stratum\Middle\BulkHandler;
use SetBased\Stratum\Middle\Helper\RowSetHelper;
use SetBased\Stratum\MySql\MySqlConnector;
use SetBased\Stratum\MySql\MySqlDataLayer;

/**
 * Class for executing SQL statements and retrieving metadata from MySQL.
 */
class AuditDataLayer extends MySqlDataLayer
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The singleton of this class.
   *
   * @var AuditDataLayer
   */
  public static AuditDataLayer $dl;

  /**
   * The Output decorator.
   *
   * @var AuditStyle
   */
  private AuditStyle $io;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * AuditDataLayer constructor.
   *
   * @param MySqlConnector $connector The object for connecting to the MySql instance.
   * @param AuditStyle     $io        The Output decorator.
   */
  public function __construct(MySqlConnector $connector, AuditStyle $io)
  {
    parent::__construct($connector);

    $this->io = $io;
    self::$dl = $this;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Adds new columns to an audit table.
   *
   * @param string               $auditSchemaName The name of audit schema.
   * @param string               $tableName       The name of the table.
   * @param TableColumnsMetadata $columns         The metadata of the new columns.
   */
  public function addNewColumns(string $auditSchemaName, string $tableName, TableColumnsMetadata $columns): void
  {
    $helper = new AlterAuditTableAddColumns($auditSchemaName, $tableName, $columns);
    $sql    = $helper->buildStatement();

    $this->executeNone($sql);
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
  public function createAuditTable(string $dataSchemaName,
                                   string $auditSchemaName,
                                   string $tableName,
                                   TableColumnsMetadata $columns): void
  {
    $helper = new CreateAuditTable($dataSchemaName, $auditSchemaName, $tableName, $columns);
    $sql    = $helper->buildStatement();

    $this->executeNone($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Creates a trigger on a table.
   *
   * @param string               $dataSchemaName         The name of the data schema.
   * @param string               $auditSchemaName        The name of the audit schema.
   * @param string               $tableName              The name of the table.
   * @param string               $triggerAction          The trigger action (i.e. INSERT, UPDATE, or DELETE).
   * @param string               $triggerName            The name of the trigger.
   * @param TableColumnsMetadata $additionalAuditColumns The metadata of the additional audit columns.
   * @param TableColumnsMetadata $tableColumns           The metadata of the data table columns.
   * @param string|null          $skipVariable           The skip variable.
   * @param string[]             $additionSql            Additional SQL statements.
   */
  public function createAuditTrigger(string $dataSchemaName,
                                     string $auditSchemaName,
                                     string $tableName,
                                     string $triggerName,
                                     string $triggerAction,
                                     TableColumnsMetadata $additionalAuditColumns,
                                     TableColumnsMetadata $tableColumns,
                                     ?string $skipVariable,
                                     array $additionSql): void
  {
    $helper = new CreateAuditTrigger($dataSchemaName,
                                     $auditSchemaName,
                                     $tableName,
                                     $triggerName,
                                     $triggerAction,
                                     $additionalAuditColumns,
                                     $tableColumns,
                                     $skipVariable,
                                     $additionSql);
    $sql    = $helper->buildStatement();

    $this->executeNone($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Creates a temporary table for getting column type information for audit columns.
   *
   * @param string  $schemaName   The name of the table schema.
   * @param string  $tableName    The table name.
   * @param array[] $auditColumns Audit columns from config file.
   */
  public function createTemporaryTable(string $schemaName, string $tableName, array $auditColumns): void
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

    $this->executeNone($sql->getCode());
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Drops a temporary table.
   *
   * @param string $schemaName The name of the table schema.
   * @param string $tableName  The name of the table.
   */
  public function dropTemporaryTable(string $schemaName, string $tableName): void
  {
    $sql = sprintf('drop table `%s`.`%s`', $schemaName, $tableName);

    $this->executeNone($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Drops a trigger.
   *
   * @param string $triggerSchema The name of the trigger schema.
   * @param string $triggerName   The mame of trigger.
   */
  public function dropTrigger(string $triggerSchema, string $triggerName): void
  {
    $sql = sprintf('drop trigger `%s`.`%s`', $triggerSchema, $triggerName);

    $this->executeNone($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  public function executeBulk(BulkHandler $bulkHandler, string $query): void
  {
    $this->logQuery($query);

    parent::executeBulk($bulkHandler, $query);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  public function executeMulti(string $queries): array
  {
    $this->logQuery($queries);

    return parent::executeMulti($queries);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  public function executeNone(string $query): int
  {
    $this->logQuery($query);

    return parent::executeNone($query);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  public function executeRow0(string $query): ?array
  {
    $this->logQuery($query);

    return parent::executeRow0($query);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  public function executeRow1(string $query): array
  {
    $this->logQuery($query);

    return parent::executeRow1($query);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  public function executeRows(string $query): array
  {
    $this->logQuery($query);

    return parent::executeRows($query);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  public function executeSingleton0(string $query)
  {
    $this->logQuery($query);

    return parent::executeSingleton0($query);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  public function executeSingleton1(string $query)
  {
    $this->logQuery($query);

    return parent::executeSingleton1($query);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  public function executeTable(string $query): int
  {
    $this->logQuery($query);

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
  public function getTableColumns(string $schemaName, string $tableName): array
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
                   $this->quoteString($schemaName),
                   $this->quoteString($tableName));

    return $this->executeRows($sql);
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
  public function getTableOptions(string $schemaName, string $tableName): array
  {
    $sql = sprintf('
select t1.TABLE_SCHEMA       as table_schema
,      t1.TABLE_NAME         as table_name
,      t1.TABLE_COLLATION    as table_collation
,      t1.ENGINE             as engine
,      t2.CHARACTER_SET_NAME as character_set_name
from       information_schema.TABLES                                t1
inner join information_schema.COLLATION_CHARACTER_SET_APPLICABILITY t2  on  t2.COLLATION_NAME = t1.TABLE_COLLATION
where t1.TABLE_SCHEMA = %s
and   t1.TABLE_NAME   = %s',
                   $this->quoteString($schemaName),
                   $this->quoteString($tableName));

    return $this->executeRow1($sql);
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
  public function getTableTriggers(string $schemaName, string $tableName): array
  {
    $sql = sprintf('
select TRIGGER_NAME as trigger_name
from   information_schema.TRIGGERS
where  TRIGGER_SCHEMA     = %s
and    EVENT_OBJECT_TABLE = %s
order by Trigger_Name',
                   $this->quoteString($schemaName),
                   $this->quoteString($tableName));

    return $this->executeRows($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects all table names in a schema.
   *
   * @param string $schemaName The name of the schema.
   *
   * @return array[]
   */
  public function getTablesNames(string $schemaName): array
  {
    $sql = sprintf("
select TABLE_NAME as table_name
from   information_schema.TABLES
where  TABLE_SCHEMA = %s
and    TABLE_TYPE   = 'BASE TABLE'
order by TABLE_NAME", $this->quoteString($schemaName));

    return $this->executeRows($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects all triggers in a schema
   *
   * @param string $schemaName The name of the table schema.
   *
   * @return array[]
   */
  public function getTriggers(string $schemaName): array
  {
    $sql = sprintf('
select EVENT_OBJECT_TABLE as table_name
,      TRIGGER_NAME       as trigger_name
from   information_schema.TRIGGERS
where  TRIGGER_SCHEMA     = %s
order by EVENT_OBJECT_TABLE
,        TRIGGER_NAME',
                   $this->quoteString($schemaName));

    return $this->executeRows($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Acquires a write lock on a table.
   *
   * @param string $schemaName The schema of the table.
   * @param string $tableName  The table name.
   */
  public function lockTable(string $schemaName, string $tableName): void
  {
    $sql = sprintf('lock tables `%s`.`%s` write', $schemaName, $tableName);

    $this->executeNone($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Resolves the canonical column types of the additional audit columns.
   *
   * @param string  $auditSchema            The name of the audit schema.
   * @param array[] $additionalAuditColumns The metadata of the additional audit columns.
   *
   * @return TableColumnsMetadata
   */
  public function resolveCanonicalAdditionalAuditColumns(string $auditSchema,
                                                         array $additionalAuditColumns): TableColumnsMetadata
  {
    if (empty($additionalAuditColumns))
    {
      return new TableColumnsMetadata([], 'AuditColumnMetadata');
    }

    $tableName = '_TMP_'.uniqid();
    $this->createTemporaryTable($auditSchema, $tableName, $additionalAuditColumns);
    $columns = AuditDataLayer::$dl->getTableColumns($auditSchema, $tableName);
    $this->dropTemporaryTable($auditSchema, $tableName);

    foreach ($additionalAuditColumns as $column)
    {
      $key = RowSetHelper::findInRowSet($columns, 'column_name', $column['column_name']);

      if (isset($column['value_type']))
      {
        $columns[$key]['value_type'] = $column['value_type'];
      }
      if (isset($column['expression']))
      {
        $columns[$key]['expression'] = $column['expression'];
      }
    }

    return new TableColumnsMetadata($columns, 'AuditColumnMetadata');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Releases all table locks.
   */
  public function unlockTables(): void
  {
    $sql = 'unlock tables';

    $this->executeNone($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Logs the query on the console.
   *
   * @param string $query The query.
   */
  private function logQuery(string $query): void
  {
    $query = trim($query);

    if (strpos($query, "\n")!==false)
    {
      // Query is a multi line query.
      $this->io->logVeryVerbose('Executing query:');
      $this->io->logVeryVerbose('<sql>%s</sql>', $query);
    }
    else
    {
      // Query is a single line query.
      $this->io->logVeryVerbose('Executing query: <sql>%s</sql>', $query);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
