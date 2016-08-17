<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\Audit\MySql;

use SetBased\Audit\MySql\Metadata\ColumnMetadata;
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
   * Creates missing audit table for this table.
   */
  public function createMissingAuditTable()
  {
    $this->io->logInfo('Creating audit table <dbo>%s.%s<dbo>', $this->auditSchemaName, $this->configTable->getTableName());

    $columns = TableColumnsMetadata::combine($this->auditColumns, $this->dataTableColumnsDatabase);
    DataLayer::createAuditTable($this->configTable->getSchemaName(), $this->auditSchemaName, $this->configTable->getTableName(), $columns);
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
    $this->dropTriggers();

    // Create or recreate the audit triggers.
    $this->createTableTrigger('INSERT', $this->skipVariable, $additionalSql);
    $this->createTableTrigger('UPDATE', $this->skipVariable, $additionalSql);
    $this->createTableTrigger('DELETE', $this->skipVariable, $additionalSql);

    // Insert, updates, and deletes are no audited again. So, release lock on the table.
    $this->unlockTables();
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
   * @return \array[] TableColumnsMetadata for config file
   */
  public function main($additionalSql)
  {
    $comparedColumns = null;
    $columns         = $this->configTable->getColumns();
    if (isset($columns))
    {
      $comparedColumns = $this->getTableColumnInfo();
    }

    $newColumns      = $comparedColumns['new_columns']->getColumns();
    $obsoleteColumns = $comparedColumns['obsolete_columns']->getColumns();
    if (empty($newColumns) && empty($obsoleteColumns))
    {
      $alteredColumns = $comparedColumns['altered_columns']->getColumns();
      if (empty($alteredColumns))
      {
        $this->createTriggers($additionalSql);
      }
    }

    return $comparedColumns;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Adds new columns to audit table.
   *
   * @param TableColumnsMetadata $columns TableColumnsMetadata array
   */
  private function addNewColumns($columns)
  {
    DataLayer::addNewColumns($this->auditSchemaName, $this->configTable->getTableName(), $columns);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Add alter property to each new columns for alter table statement.
   *
   * @param TableColumnsMetadata $newColumns
   *
   * @return TableColumnsMetadata
   */
  private function alterNewColumns($newColumns)
  {
    $alterNewColumns = [];
    /** @var ColumnMetadata $column */
    foreach ($newColumns->getColumns() as $key => $column)
    {
      $dataTableColumns = $this->dataTableColumnsDatabase->getColumns();
      $prev             = null;
      /** @var ColumnMetadata $dataColumn */
      foreach ($dataTableColumns as $columnName => $dataColumn)
      {
        if ($columnName===$key)
        {
          $alterNewColumns[$key]          = $column->getProperties();
          $alterNewColumns[$key]['after'] = $prev;
        }
        $prev = $columnName;
      }
    }

    return new TableColumnsMetadata($alterNewColumns, '\SetBased\Audit\MySql\Metadata\AlterColumnMetadata');
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

    DataLayer::createAuditTrigger($this->configTable->getSchemaName(),
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
   * Drops all triggers from this table.
   */
  private function dropTriggers()
  {
    $triggers = DataLayer::getTableTriggers($this->configTable->getSchemaName(), $this->configTable->getTableName());
    foreach ($triggers as $trigger)
    {
      $this->io->logVerbose('Dropping trigger <dbo>%s</dbo> on <dbo>%s.%s</dbo>',
                            $trigger['trigger_name'],
                            $this->configTable->getSchemaName(),
                            $this->configTable->getTableName());

      DataLayer::dropTrigger($this->configTable->getSchemaName(), $trigger['trigger_name']);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Compares columns types from table in data_schema with columns in config file.
   *
   * @return TableColumnsMetadata
   */
  private function getAlteredColumns()
  {
    $alteredColumnsTypes = TableColumnsMetadata::differentColumnTypes($this->dataTableColumnsDatabase,
                                                                      $this->configTable->getColumns());

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
    $result = DataLayer::getTableColumns($this->configTable->getSchemaName(), $this->configTable->getTableName());

    return $result;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Compare columns from table in data_schema with columns in config file.
   *
   * @return \array[]
   */
  private function getTableColumnInfo()
  {
    $columnActual  = new TableColumnsMetadata(DataLayer::getTableColumns($this->auditSchemaName, $this->configTable->getTableName()));
    $columnsConfig = TableColumnsMetadata::combine($this->auditColumns, $this->configTable->getColumns());
    $columnsTarget = TableColumnsMetadata::combine($this->auditColumns, $this->dataTableColumnsDatabase);

    $newColumns      = TableColumnsMetadata::notInOtherSet($columnsTarget, $columnActual);
    $obsoleteColumns = TableColumnsMetadata::notInOtherSet($columnsConfig, $columnsTarget);
    $alteredColumns  = $this->getAlteredColumns();

    $this->loggingColumnInfo($newColumns, $obsoleteColumns, $alteredColumns);

    $alterNewColumns = $this->alterNewColumns($newColumns);
    $this->addNewColumns($alterNewColumns);

    return ['full_columns'     => $this->getTableColumnsFromConfig($newColumns, $obsoleteColumns),
            'new_columns'      => $newColumns,
            'obsolete_columns' => $obsoleteColumns,
            'altered_columns'  => $alteredColumns];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Check for know what columns array returns.
   *
   * @param TableColumnsMetadata $newColumns
   * @param TableColumnsMetadata $obsoleteColumns
   *
   * @return TableColumnsMetadata
   */
  private function getTableColumnsFromConfig($newColumns, $obsoleteColumns)
  {
    $new      = $newColumns->getColumns();
    $obsolete = $obsoleteColumns->getColumns();
    if (!empty($new) && !empty($obsolete))
    {
      return $this->configTable->getColumns();
    }

    return $this->dataTableColumnsDatabase;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Create and return trigger name.
   *
   * @param string $action Trigger on action (Insert, Update, Delete)
   *
   * @return string
   */
  private function getTriggerName($action)
  {
    return strtolower(sprintf('trg_%s_%s', $this->alias, $action));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Lock the table to prevent insert, updates, or deletes between dropping and creating triggers.
   *
   * @param string $tableName Name of table
   */
  private function lockTable($tableName)
  {
    DataLayer::lockTable($tableName);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Logging new and obsolete columns.
   *
   * @param TableColumnsMetadata $newColumns
   * @param TableColumnsMetadata $obsoleteColumns
   * @param TableColumnsMetadata $alteredColumns
   */
  private function loggingColumnInfo($newColumns, $obsoleteColumns, $alteredColumns)
  {
    $new      = $newColumns->getColumns();
    $obsolete = $obsoleteColumns->getColumns();
    if (!empty($new) && !empty($obsolete))
    {
      $this->io->logInfo('Found both new and obsolete columns for table %s', $this->configTable->getTableName());
      $this->io->logInfo('No action taken');

      /** @var ColumnMetadata $column */
      foreach ($newColumns->getColumns() as $column)
      {
        $this->io->logInfo('New column %s', $column->getProperty('column_name'));
      }
      foreach ($obsoleteColumns->getColumns() as $column)
      {
        $this->io->logInfo('Obsolete column %s', $column->getProperty('column_name'));
      }
    }

    /** @var ColumnMetadata $column */
    foreach ($obsoleteColumns->getColumns() as $column)
    {
      $this->io->logInfo('Obsolete column %s.%s', $this->configTable->getTableName(), $column->getProperty('column_name'));
    }

    /** @var ColumnMetadata $column */
    foreach ($newColumns->getColumns() as $column)
    {
      $this->io->logInfo('New column %s.%s', $this->configTable->getTableName(), $column->getProperty('column_name'));
    }

    foreach ($alteredColumns->getColumns() as $column)
    {
      $this->io->logInfo('Type of <dbo>%s.%s</dbo> has been altered to <dbo>%s</dbo>',
                         $this->configTable->getTableName(),
                         $column['column_name'],
                         $column['column_type']);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Releases all table locks.
   */
  private function unlockTables()
  {
    DataLayer::unlockTables();
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
