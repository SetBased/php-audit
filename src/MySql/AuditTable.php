<?php

namespace SetBased\Audit\MySql;

use SetBased\Audit\MySql\Metadata\AlterColumnMetadata;
use SetBased\Audit\MySql\Metadata\TableColumnsMetadata;
use SetBased\Audit\MySql\Metadata\TableMetadata;
use SetBased\Stratum\Style\StratumStyle;

//--------------------------------------------------------------------------------------------------------------------
/**
 * Class for work on table with all column like audit,data and config.
 */
class AuditTable
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The unique alias for this data table.
   *
   * @var string
   */
  private $alias;

  /**
   * The metadata (additional) audit columns (as stored in the config file).
   *
   * @var TableColumnsMetadata
   */
  private $auditColumns;

  /**
   * The name of the schema with the audit tables.
   *
   * @var string
   */
  private $auditSchemaName;

  /**
   * The table metadata.
   *
   * @var TableMetadata
   */
  private $configTable;

  /**
   * The metadata of the columns of the data table retrieved from information_schema.
   *
   * @var TableColumnsMetadata
   */
  private $dataTableColumnsDatabase;

  /**
   * The output decorator
   *
   * @var StratumStyle
   */
  private $io;

  /**
   * The skip variable for triggers.
   *
   * @var string
   */
  private $skipVariable;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param StratumStyle         $io                   The output for log messages.
   * @param TableMetadata        $configTable          The table meta data.
   * @param string               $auditSchema          The name of the schema with audit tables.
   * @param TableColumnsMetadata $auditColumnsMetadata The columns of the audit table as stored in the config file.
   * @param string               $alias                An unique alias for this table.
   * @param string               $skipVariable         The skip variable
   */
  public function __construct($io,
                              $configTable,
                              $auditSchema,
                              $auditColumnsMetadata,
                              $alias,
                              $skipVariable)
  {
    $this->io                       = $io;
    $this->configTable              = $configTable;
    $this->auditSchemaName          = $auditSchema;
    $this->dataTableColumnsDatabase = new TableColumnsMetadata($this->getColumnsFromInformationSchema());
    $this->auditColumns             = $auditColumnsMetadata;
    $this->alias                    = $alias;
    $this->skipVariable             = $skipVariable;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns a random alias for this table.
   *
   * @return string
   */
  public static function getRandomAlias()
  {
    return uniqid();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Creates an audit table for this table.
   */
  public function createAuditTable()
  {
    $this->io->logInfo('Creating audit table <dbo>%s.%s<dbo>',
                       $this->auditSchemaName,
                       $this->configTable->getTableName());

    // In the audit table all columns from the data table must be nullable.
    $dataTableColumnsDatabase = clone($this->dataTableColumnsDatabase);
    $dataTableColumnsDatabase->makeNullable();

    $columns = TableColumnsMetadata::combine($this->auditColumns, $dataTableColumnsDatabase);
    AuditDataLayer::createAuditTable($this->configTable->getSchemaName(),
                                     $this->auditSchemaName,
                                     $this->configTable->getTableName(),
                                     $columns);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Creates audit triggers on this table.
   *
   * @param string[] $additionalSql Additional SQL statements to be include in triggers.
   */
  public function createTriggers($additionalSql)
  {
    // Lock the table to prevent insert, updates, or deletes between dropping and creating triggers.
    $this->lockTable($this->configTable->getTableName());

    // Drop all triggers, if any.
    $this->dropAuditTriggers($this->configTable->getSchemaName(), $this->configTable->getTableName());

    // Create or recreate the audit triggers.
    $this->createTableTrigger('INSERT', $this->skipVariable, $additionalSql);
    $this->createTableTrigger('UPDATE', $this->skipVariable, $additionalSql);
    $this->createTableTrigger('DELETE', $this->skipVariable, $additionalSql);

    // Insert, updates, and deletes are no audited again. So, release lock on the table.
    $this->unlockTables();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Drops all audit triggers from this table.
   *
   * @param string $schemaName The name of the table schema.
   * @param string $tableName  The name of the table.
   */
  public function dropAuditTriggers($schemaName, $tableName)
  {
    $triggers = AuditDataLayer::getTableTriggers($schemaName, $tableName);
    foreach ($triggers as $trigger)
    {
      if (preg_match('/^trg_audit_.*_(insert|update|delete)$/', $trigger['trigger_name']))
      {
        $this->io->logVerbose('Dropping trigger <dbo>%s</dbo> on <dbo>%s.%s</dbo>',
                              $trigger['trigger_name'],
                              $schemaName,
                              $tableName);

        AuditDataLayer::dropTrigger($schemaName, $trigger['trigger_name']);
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the table name.
   *
   * @return string
   */
  public function getTableName()
  {
    return $this->configTable->getTableName();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Main function for work with table.
   *
   * @param string[] $additionalSql Additional SQL statements to be include in triggers.
   *
   * @return bool
   */
  public function main($additionalSql)
  {
    $comparedColumns = $this->getTableColumnInfo();
    $newColumns      = $comparedColumns['new_columns'];
    $obsoleteColumns = $comparedColumns['obsolete_columns'];
    $alteredColumns  = $comparedColumns['altered_columns'];

    if ($newColumns->getNumberOfColumns()==0 || $obsoleteColumns->getNumberOfColumns()==0)
    {
      $this->addNewColumns($newColumns);

      $this->createTriggers($additionalSql);

      return ($alteredColumns->getNumberOfColumns()==0);
    }

    $this->io->warning(sprintf('Skipping table %s', $this->getTableName()));

    return false;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Adds new columns to audit table.
   *
   * @param TableColumnsMetadata $columns TableColumnsMetadata array
   */
  private function addNewColumns($columns)
  {
    // Return immediately if there are no columns to add.
    if ($columns->getNumberOfColumns()==0) return;

    $alterColumns = $this->alterNewColumns($columns);

    AuditDataLayer::addNewColumns($this->auditSchemaName, $this->configTable->getTableName(), $alterColumns);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns metadata of new table columns that can be used in a 'alter table .. add column' statement.
   *
   * @param TableColumnsMetadata $newColumns The metadata new table columns.
   *
   * @return TableColumnsMetadata
   */
  private function alterNewColumns($newColumns)
  {
    $alterNewColumns = new TableColumnsMetadata();
    foreach ($newColumns->getColumns() as $newColumn)
    {
      $properties          = $newColumn->getProperties();
      $properties['after'] = $this->dataTableColumnsDatabase->getPreviousColumn($properties['column_name']);

      $alterNewColumns->appendTableColumn(new AlterColumnMetadata($properties));
    }

    return $alterNewColumns;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Creates a triggers for this table.
   *
   * @param string      $action      The trigger action (INSERT, DELETE, or UPDATE).
   * @param string|null $skipVariable
   * @param string[]    $additionSql The additional SQL statements to be included in triggers.
   */
  private function createTableTrigger($action, $skipVariable, $additionSql)
  {
    $triggerName = $this->getTriggerName($action);

    $this->io->logVerbose('Creating trigger <dbo>%s.%s</dbo> on table <dbo>%s.%s</dbo>',
                          $this->configTable->getSchemaName(),
                          $triggerName,
                          $this->configTable->getSchemaName(),
                          $this->configTable->getTableName());

    AuditDataLayer::createAuditTrigger($this->configTable->getSchemaName(),
                                       $this->auditSchemaName,
                                       $this->configTable->getTableName(),
                                       $triggerName,
                                       $action,
                                       $this->auditColumns,
                                       $this->dataTableColumnsDatabase,
                                       $skipVariable,
                                       $additionSql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Compares columns types from table in data_schema with columns in config file.
   *
   * @return TableColumnsMetadata
   */
  private function getAlteredColumns()
  {
    if ($this->configTable->getColumns()->getNumberOfColumns())
    {
      $alteredColumnsTypes = TableColumnsMetadata::differentColumnTypes($this->dataTableColumnsDatabase,
                                                                        $this->configTable->getColumns(),
                                                                        ['is_nullable']);
    }
    else
    {
      $alteredColumnsTypes = new TableColumnsMetadata();
    }

    return $alteredColumnsTypes;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects and returns the metadata of the columns of this table from information_schema.
   *
   * @return array[]
   */
  private function getColumnsFromInformationSchema()
  {
    $result = AuditDataLayer::getTableColumns($this->configTable->getSchemaName(), $this->configTable->getTableName());

    return $result;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Compare columns from table in data_schema with columns in config file.
   *
   * @return array<string,TableColumnsMetadata>
   */
  private function getTableColumnInfo()
  {
    $columnActual  = new TableColumnsMetadata(AuditDataLayer::getTableColumns($this->auditSchemaName,
                                                                              $this->configTable->getTableName()));
    $columnsConfig = TableColumnsMetadata::combine($this->auditColumns, $this->configTable->getColumns());
    $columnsTarget = TableColumnsMetadata::combine($this->auditColumns, $this->dataTableColumnsDatabase);

    $newColumns      = TableColumnsMetadata::notInOtherSet($columnsTarget, $columnActual);
    $obsoleteColumns = TableColumnsMetadata::notInOtherSet($columnsConfig, $columnsTarget);
    $alteredColumns  = $this->getAlteredColumns();

    $this->logColumnInfo($newColumns, $obsoleteColumns, $alteredColumns);

    return ['new_columns'      => $newColumns,
            'obsolete_columns' => $obsoleteColumns,
            'altered_columns'  => $alteredColumns];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the trigger name for a trigger action.
   *
   * @param string $action Trigger on action (Insert, Update, Delete)
   *
   * @return string
   */
  private function getTriggerName($action)
  {
    return strtolower(sprintf('trg_audit_%s_%s', $this->alias, $action));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Lock the table to prevent insert, updates, or deletes between dropping and creating triggers.
   *
   * @param string $tableName Name of table
   */
  private function lockTable($tableName)
  {
    AuditDataLayer::lockTable($tableName);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Logs info about new, obsolete, and altered columns.
   *
   * @param TableColumnsMetadata $newColumns      The metadata of the new columns.
   * @param TableColumnsMetadata $obsoleteColumns The metadata of the obsolete columns.
   * @param TableColumnsMetadata $alteredColumns  The metadata of the altered columns.
   */
  private function logColumnInfo($newColumns, $obsoleteColumns, $alteredColumns)
  {
    foreach ($newColumns->getColumns() as $column)
    {
      $this->io->logInfo('New column <dbo>%s.%s</dbo>',
                         $this->configTable->getTableName(),
                         $column->getProperty('column_name'));
    }

    foreach ($obsoleteColumns->getColumns() as $column)
    {
      $this->io->logInfo('Obsolete column <dbo>%s.%s</dbo>',
                         $this->configTable->getTableName(),
                         $column->getProperty('column_name'));
    }

    foreach ($alteredColumns->getColumns() as $column)
    {
      $this->io->logInfo('Type of <dbo>%s.%s</dbo> has been altered to <dbo>%s</dbo>',
                         $this->configTable->getTableName(),
                         $column->getProperty('column_name'),
                         $column->getProperty('column_type'));
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Releases all table locks.
   */
  private function unlockTables()
  {
    AuditDataLayer::unlockTables();
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
