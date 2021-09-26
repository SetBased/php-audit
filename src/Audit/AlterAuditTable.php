<?php
declare(strict_types=1);

namespace SetBased\Audit\Audit;

use SetBased\Audit\Metadata\TableColumnsMetadata;
use SetBased\Audit\MySql\AlterTableCodeStore;
use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Audit\MySql\Metadata\TableMetadata;
use SetBased\Config\TypedConfig;
use SetBased\Exception\FallenException;

/**
 * Class for generating alter audit table SQL statements for manual evaluation.
 */
class AlterAuditTable
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The metadata of the additional audit columns.
   *
   * @var TableColumnsMetadata
   */
  private TableColumnsMetadata $additionalAuditColumns;

  /**
   * Code store for alter table statement.
   *
   * @var AlterTableCodeStore
   */
  private AlterTableCodeStore $codeStore;

  /**
   * The strong typed configuration reader and writer.
   *
   * @var TypedConfig
   */
  private TypedConfig $config;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param TypedConfig $config The strong typed configuration reader and writer.
   */
  public function __construct(TypedConfig $config)
  {
    $this->config    = $config;
    $this->codeStore = new AlterTableCodeStore();

    $this->additionalAuditColumns =
      AuditDataLayer::$dl->resolveCanonicalAdditionalAuditColumns($this->config->getManString('database.audit_schema'),
                                                                  $this->config->getManArray('audit_columns'));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The main method: executes the create alter table statement actions for tables.
   *
   * return string
   */
  public function main(): string
  {
    $tables = $this->getTableList();
    foreach ($tables as $table)
    {
      $this->compareTable($table);
    }

    return $this->codeStore->getCode();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Compares a table in the data schema and its counter part in the audit schema.
   *
   * @param string $tableName The name of the table.
   */
  private function compareTable(string $tableName): void
  {
    $dataTable  = $this->getTableMetadata($this->config->getManString('database.data_schema'), $tableName);
    $auditTable = $this->getTableMetadata($this->config->getManString('database.audit_schema'), $tableName);

    // In the audit schema columns corresponding with the columns from the data table are always nullable.
    $dataTable->getColumns()->makeNullable();
    $dataTable->getColumns()->prependTableColumns($this->additionalAuditColumns);

    $this->compareTableOptions($dataTable, $auditTable);
    $this->compareTableColumns($dataTable, $auditTable);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Compares the columns of the data and audit tables and generates the appropriate alter table statement.
   *
   * @param TableMetadata $dataTable  The metadata of the data table.
   * @param TableMetadata $auditTable The metadata of the audit table.
   */
  private function compareTableColumns(TableMetadata $dataTable, TableMetadata $auditTable): void
  {
    $diff = TableColumnsMetadata::differentColumnTypes($dataTable->getColumns(), $auditTable->getColumns());

    if (!empty($diff->getColumns()))
    {
      $maxLength = $diff->getLongestColumnNameLength();

      $this->codeStore->append(sprintf('alter table `%s`.`%s`',
                                       $this->config->getManString('database.audit_schema'),
                                       $auditTable->getTableName()));

      $first = true;
      foreach ($diff->getColumns() as $column)
      {
        $name   = $column->getName();
        $filler = str_repeat(' ', $maxLength - mb_strlen($name) + 1);

        if (!$first)
        {
          $this->codeStore->appendToLastLine(',');
        }

        $this->codeStore->append(sprintf('change column `%s`%s`%s`%s%s',
                                         $name,
                                         $filler,
                                         $name,
                                         $filler,
                                         $column->getColumnAuditDefinition()));

        $first = false;
      }

      $this->codeStore->append(';');
      $this->codeStore->append('');
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Compares the table options of the data and audit tables and generates the appropriate alter table statement.
   *
   * @param TableMetadata $dataTable  The metadata of the data table.
   * @param TableMetadata $auditTable The metadata of the audit table.
   */
  private function compareTableOptions(TableMetadata $dataTable, TableMetadata $auditTable): void
  {
    $options = TableMetadata::compareOptions($dataTable, $auditTable);

    if (!empty($options))
    {
      $parts = [];
      foreach ($options as $option)
      {
        switch ($option)
        {
          case 'engine':
            $parts[] = 'engine '.$dataTable->getProperty('engine');
            break;

          case 'character_set_name':
            $parts[] = 'default character set '.$dataTable->getProperty('character_set_name');
            break;

          case 'table_collation':
            $parts[] = 'default collate '.$dataTable->getProperty('table_collation');
            break;

          default:
            throw new FallenException('option', $option);
        }
      }

      $this->codeStore->append(sprintf('alter table `%s`.`%s` %s;',
                                       $this->config->getManString('database.audit_schema'),
                                       $auditTable->getTableName(),
                                       implode(' ', $parts)));
      $this->codeStore->append('');
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the names of the tables that must be compared.
   *
   * @return string[]
   */
  private function getTableList(): array
  {
    $tables1 = [];
    foreach ($this->config->getManArray('tables') as $tableName => $config)
    {
      if ($config['audit'])
      {
        $tables1[] = $tableName;
      }
    }

    $tables  = AuditDataLayer::$dl->getTablesNames($this->config->getManString('database.data_schema'));
    $tables2 = [];
    foreach ($tables as $table)
    {
      $tables2[] = $table['table_name'];
    }

    $tables  = AuditDataLayer::$dl->getTablesNames($this->config->getManString('database.audit_schema'));
    $tables3 = [];
    foreach ($tables as $table)
    {
      $tables3[] = $table['table_name'];
    }

    return array_intersect($tables1, $tables2, $tables3);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the metadata of a table.
   *
   * @param string $schemaName The name of the schema of the table.
   * @param string $tableName  The name of the table.
   *
   * @return TableMetadata
   */
  private function getTableMetadata(string $schemaName, string $tableName): TableMetadata
  {
    $table   = AuditDataLayer::$dl->getTableOptions($schemaName, $tableName);
    $columns = AuditDataLayer::$dl->getTableColumns($schemaName, $tableName);

    return new TableMetadata($table, new TableColumnsMetadata($columns));
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
