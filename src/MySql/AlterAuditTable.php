<?php

namespace SetBased\Audit\MySql;

use SetBased\Audit\MySql\Helper\MySqlAlterTableCodeStore;
use SetBased\Audit\MySql\Metadata\TableColumnsMetadata;
use SetBased\Audit\MySql\Metadata\TableMetadata;
use SetBased\Exception\FallenException;

/**
 * Class for generating alter audit table SQL statements for manual evaluation.
 */
class AlterAuditTable
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The metadata (additional) audit columns (as stored in the config file).
   *
   * @var TableColumnsMetadata
   */
  private $auditColumnsMetadata;

  /**
   * Code store for alter table statement.
   *
   * @var MySqlAlterTableCodeStore
   */
  private $codeStore;

  /**
   * The content of the configuration file.
   *
   * @var array
   */
  private $config;

  //--------------------------------------------------------------------------------------------------------------------

  /**
   * Object constructor.
   *
   * @param array[] $config The content of the configuration file.
   */
  public function __construct(&$config)
  {
    $this->config    = &$config;
    $this->codeStore = new MySqlAlterTableCodeStore();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The main method: executes the create alter table statement actions for tables.
   *
   * return string
   */
  public function main()
  {
    $this->resolveCanonicalAuditColumns();

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
  private function compareTable($tableName)
  {
    $dataTable  = $this->getTableMetadata($this->config['database']['data_schema'], $tableName);
    $auditTable = $this->getTableMetadata($this->config['database']['audit_schema'], $tableName);

    // In the audit schema columns corresponding with the columns from the data table are always nullable.
    $dataTable->getColumns()->makeNullable();
    $dataTable->getColumns()->prependTableColumns($this->auditColumnsMetadata);

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
  private function compareTableColumns($dataTable, $auditTable)
  {
    $diff = TableColumnsMetadata::differentColumnTypes($dataTable->getColumns(), $auditTable->getColumns());

    if (!empty($diff->getColumns()))
    {
      $maxLength = $diff->getLongestColumnNameLength();

      $this->codeStore->append(sprintf('alter table `%s`.`%s`',
                                       $this->config['database']['audit_schema'],
                                       $auditTable->getTableName()));

      $first = true;
      foreach ($diff->getColumns() as $column)
      {
        $name   = $column->getName();
        $filler = str_repeat(' ', $maxLength - mb_strlen($name) + 1);

        if (!$first) $this->codeStore->appendToLastLine(',');

        $this->codeStore->append(sprintf('change column `%s`%s`%s`%s%s',
                                         $name,
                                         $filler,
                                         $name,
                                         $filler,
                                         $column->getColumnDefinition()));

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
  private function compareTableOptions($dataTable, $auditTable)
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
                                       $this->config['database']['audit_schema'],
                                       $auditTable->getTableName(),
                                       implode(' ', $parts)));
      $this->codeStore->append('');
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the names of the tables that must be compared.
   */
  private function getTableList()
  {
    $tables1 = [];
    foreach ($this->config['tables'] as $tableName => $config)
    {
      if ($config['audit'])
      {
        $tables1[] = $tableName;
      }
    }

    $tables  = AuditDataLayer::getTablesNames($this->config['database']['data_schema']);
    $tables2 = [];
    foreach ($tables as $table)
    {
      $tables2[] = $table['table_name'];
    }

    $tables  = AuditDataLayer::getTablesNames($this->config['database']['audit_schema']);
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
  private function getTableMetadata($schemaName, $tableName)
  {
    $table   = AuditDataLayer::getTableOptions($schemaName, $tableName);
    $columns = AuditDataLayer::getTableColumns($schemaName, $tableName);

    return new TableMetadata($table, $columns);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Resolves the canonical column types of the audit table columns.
   */
  private function resolveCanonicalAuditColumns()
  {
    if (empty($this->config['audit_columns']))
    {
      $this->auditColumnsMetadata = new TableColumnsMetadata([], 'AuditColumnMetadata');
    }
    else
    {
      $schema    = $this->config['database']['audit_schema'];
      $tableName = '_TMP_'.uniqid();
      AuditDataLayer::createTemporaryTable($schema, $tableName, $this->config['audit_columns']);
      $columns = AuditDataLayer::getTableColumns($schema, $tableName);
      AuditDataLayer::dropTemporaryTable($schema, $tableName);

      foreach ($this->config['audit_columns'] as $audit_column)
      {
        $key = AuditDataLayer::searchInRowSet('column_name', $audit_column['column_name'], $columns);

        if (isset($audit_column['value_type']))
        {
          $columns[$key]['value_type'] = $audit_column['value_type'];
        }
        if (isset($audit_column['expression']))
        {
          $columns[$key]['expression'] = $audit_column['expression'];
        }
      }

      $this->auditColumnsMetadata = new TableColumnsMetadata($columns, 'AuditColumnMetadata');
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
