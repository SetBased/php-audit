<?php
declare(strict_types=1);

namespace SetBased\Audit;

use SetBased\Audit\Metadata\TableColumnsMetadata;
use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Audit\MySql\Metadata\AlterColumnMetadata;
use SetBased\Audit\Style\AuditStyle;

/**
 * Class for creating audit tables and triggers.
 */
class AuditTable
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The metadata of the additional audit columns.
   *
   * @var TableColumnsMetadata
   */
  private TableColumnsMetadata $additionalAuditColumns;

  /**
   * The unique alias for this data table.
   *
   * @var string|null
   */
  private ?string $alias;

  /**
   * The name of the schema with the audit tables.
   *
   * @var string
   */
  private string $auditSchemaName;

  /**
   * The name of the schema with the data tables.
   *
   * @var string
   */
  protected $dataSchemaName;

  /**
   * The metadata of the columns of the data table retrieved from information_schema.
   *
   * @var TableColumnsMetadata
   */
  private TableColumnsMetadata $dataTableColumnsDatabase;

  /**
   * The output decorator
   *
   * @var AuditStyle
   */
  private AuditStyle $io;

  /**
   * The skip variable for triggers.
   *
   * @var string|null
   */
  private ?string $skipVariable;

  /**
   * The name of the data and audit table.
   *
   * @var string
   */
  private string $tableName;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param AuditStyle           $io                     The output for log messages.
   * @param string               $dataSchemaName         The name of the schema with data tables.
   * @param string               $auditSchemaName        The name of the schema with audit tables.
   * @param string               $tableName              The name of the data and audit table.
   * @param TableColumnsMetadata $additionalAuditColumns The metadata of the additional audit columns.
   * @param string|null          $alias                  An unique alias for this table.
   * @param string|null          $skipVariable           The skip variable
   */
  public function __construct(AuditStyle $io,
                              string $dataSchemaName,
                              string $auditSchemaName,
                              string $tableName,
                              TableColumnsMetadata $additionalAuditColumns,
                              ?string $alias,
                              ?string $skipVariable)
  {
    $this->io                       = $io;
    $this->dataSchemaName           = $dataSchemaName;
    $this->auditSchemaName          = $auditSchemaName;
    $this->tableName                = $tableName;
    $this->dataTableColumnsDatabase = new TableColumnsMetadata($this->getColumnsFromInformationSchema());
    $this->additionalAuditColumns   = $additionalAuditColumns;
    $this->alias                    = $alias;
    $this->skipVariable             = $skipVariable;

    $this->dataTableColumnsDatabase->makeNullable();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Drops all audit triggers from a table.
   *
   * @param AuditStyle $io         The output decorator.
   * @param string     $schemaName The name of the table schema.
   * @param string     $tableName  The name of the table.
   */
  public static function dropAuditTriggers(AuditStyle $io, string $schemaName, string $tableName): void
  {
    $triggers = AuditDataLayer::$dl->getTableTriggers($schemaName, $tableName);
    foreach ($triggers as $trigger)
    {
      if (preg_match('/^trg_audit_.*_(insert|update|delete)$/', $trigger['trigger_name']))
      {
        $io->logVerbose('Dropping trigger <dbo>%s</dbo> on <dbo>%s.%s</dbo>',
                        $trigger['trigger_name'],
                        $schemaName,
                        $tableName);

        AuditDataLayer::$dl->dropTrigger($schemaName, $trigger['trigger_name']);
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns a random alias for this table.
   *
   * @return string
   */
  public static function getRandomAlias(): string
  {
    return uniqid();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Creates an audit table for this table.
   */
  public function createAuditTable(): void
  {
    $this->io->logInfo('Creating audit table <dbo>%s.%s<dbo>', $this->auditSchemaName, $this->tableName);

    // In the audit table all columns from the data table must be nullable.
    $dataTableColumnsDatabase = clone($this->dataTableColumnsDatabase);
    $dataTableColumnsDatabase->makeNullable();

    $columns = TableColumnsMetadata::combine($this->additionalAuditColumns, $dataTableColumnsDatabase);
    AuditDataLayer::$dl->createAuditTable($this->dataSchemaName, $this->auditSchemaName, $this->tableName, $columns);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Creates audit triggers on this table.
   *
   * @param string[] $additionalSql Additional SQL statements to be include in triggers.
   */
  public function createTriggers(array $additionalSql): void
  {
    // Lock the table to prevent insert, updates, or deletes between dropping and creating triggers.
    $this->lockTable();

    // Drop all triggers, if any.
    static::dropAuditTriggers($this->io, $this->dataSchemaName, $this->tableName);

    // Create or recreate the audit triggers.
    $this->createTableTrigger('INSERT', $this->skipVariable, $additionalSql);
    $this->createTableTrigger('UPDATE', $this->skipVariable, $additionalSql);
    $this->createTableTrigger('DELETE', $this->skipVariable, $additionalSql);

    // Insert, updates, and deletes are no audited again. So, release lock on the table.
    $this->unlockTable();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the table name.
   *
   * @return string
   */
  public function getTableName(): string
  {
    return $this->tableName;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Main function for work with table.
   *
   * @param string[] $additionalSql Additional SQL statements to be include in triggers.
   */
  public function main(array $additionalSql): void
  {
    $comparedColumns = $this->getTableColumnInfo();
    $newColumns      = $comparedColumns['new_columns'];

    $this->addNewColumns($newColumns);
    $this->createTriggers($additionalSql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Adds new columns to audit table.
   *
   * @param TableColumnsMetadata $columns TableColumnsMetadata array
   */
  private function addNewColumns(TableColumnsMetadata $columns): void
  {
    // Return immediately if there are no columns to add.
    if ($columns->getNumberOfColumns()===0) return;

    $alterColumns = $this->alterNewColumns($columns);

    AuditDataLayer::$dl->addNewColumns($this->auditSchemaName, $this->tableName, $alterColumns);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns metadata of new table columns that can be used in a 'alter table .. add column' statement.
   *
   * @param TableColumnsMetadata $newColumns The metadata new table columns.
   *
   * @return TableColumnsMetadata
   */
  private function alterNewColumns(TableColumnsMetadata $newColumns): TableColumnsMetadata
  {
    $alterNewColumns = new TableColumnsMetadata();
    foreach ($newColumns->getColumns() as $newColumn)
    {
      $properties = $newColumn->getProperties();
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
  private function createTableTrigger(string $action, ?string $skipVariable, array $additionSql): void
  {
    $triggerName = $this->getTriggerName($action);

    $this->io->logVerbose('Creating trigger <dbo>%s.%s</dbo> on table <dbo>%s.%s</dbo>',
                          $this->dataSchemaName,
                          $triggerName,
                          $this->dataSchemaName,
                          $this->tableName);

    AuditDataLayer::$dl->createAuditTrigger($this->dataSchemaName,
                                            $this->auditSchemaName,
                                            $this->tableName,
                                            $triggerName,
                                            $action,
                                            $this->additionalAuditColumns,
                                            $this->dataTableColumnsDatabase,
                                            $skipVariable,
                                            $additionSql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects and returns the metadata of the columns of this table from information_schema.
   *
   * @return array[]
   */
  private function getColumnsFromInformationSchema(): array
  {
    return AuditDataLayer::$dl->getTableColumns($this->dataSchemaName, $this->tableName);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Compare columns from table in data_schema with columns in config file.
   *
   * @return array<string,TableColumnsMetadata>
   */
  private function getTableColumnInfo(): array
  {
    $actual = new TableColumnsMetadata(AuditDataLayer::$dl->getTableColumns($this->auditSchemaName, $this->tableName));
    $target = TableColumnsMetadata::combine($this->additionalAuditColumns, $this->dataTableColumnsDatabase);
    $target->enhanceAfter();

    $new      = TableColumnsMetadata::notInOtherSet($target, $actual);
    $obsolete = TableColumnsMetadata::notInOtherSet($actual, $target);
    $altered  = TableColumnsMetadata::differentColumnTypes($actual, $target, ['is_nullable']);

    $this->logColumnInfo($new, $obsolete, $altered);

    return ['new_columns'      => $new,
            'obsolete_columns' => $obsolete,
            'altered_columns'  => $altered];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the trigger name for a trigger action.
   *
   * @param string $action Trigger on action (Insert, Update, Delete)
   *
   * @return string
   */
  private function getTriggerName(string $action): string
  {
    return strtolower(sprintf('trg_audit_%s_%s', $this->alias, $action));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Lock the data table to prevent insert, updates, or deletes between dropping and creating triggers.
   */
  private function lockTable(): void
  {
    AuditDataLayer::$dl->lockTable($this->dataSchemaName, $this->tableName);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Logs info about new, obsolete, and altered columns.
   *
   * @param TableColumnsMetadata $newColumns      The metadata of the new columns.
   * @param TableColumnsMetadata $obsoleteColumns The metadata of the obsolete columns.
   * @param TableColumnsMetadata $alteredColumns  The metadata of the altered columns.
   */
  private function logColumnInfo(TableColumnsMetadata $newColumns,
                                 TableColumnsMetadata $obsoleteColumns,
                                 TableColumnsMetadata $alteredColumns): void
  {
    foreach ($newColumns->getColumns() as $column)
    {
      $this->io->logInfo('New column <dbo>%s.%s</dbo>',
                         $this->tableName,
                         $column->getName());
    }

    foreach ($obsoleteColumns->getColumns() as $column)
    {
      $this->io->logInfo('Obsolete column <dbo>%s.%s</dbo>',
                         $this->tableName,
                         $column->getName());
    }

    foreach ($alteredColumns->getColumns() as $column)
    {
      $this->io->logInfo('Type of <dbo>%s.%s</dbo> has been altered to <dbo>%s</dbo>',
                         $this->tableName,
                         $column->getName(),
                         $column->getProperty('column_type'));
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Releases the table lock.
   */
  private function unlockTable(): void
  {
    AuditDataLayer::$dl->unlockTables();
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
