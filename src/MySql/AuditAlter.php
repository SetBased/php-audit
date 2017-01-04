<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\Audit\MySql;

use SetBased\Audit\MySql\Helper\MySqlAlterTableCodeStore;
use SetBased\Audit\MySql\Metadata\ColumnMetadata;
use SetBased\Audit\MySql\Metadata\MultiSourceColumnMetadata;
use SetBased\Audit\MySql\Metadata\TableColumnsMetadata;

//----------------------------------------------------------------------------------------------------------------------
/**
 * Class for executing auditing actions for tables.
 */
class AuditAlter
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The metadata (additional) audit columns (as stored in the config file).
   *
   * @var \array[]
   */
  private $auditColumnsMetadata;

  /**
   * The names of all tables in audit schema.
   *
   * @var array
   */
  private $auditSchemaTables;

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

  /**
   * Config metadata columns.
   *
   * @var array
   */
  private $configMetadata;

  /**
   * The names of all tables in data schema.
   *
   * @var array
   */
  private $dataSchemaTables;

  /**
   * Array with columns for each table.
   *
   * @var array<string,AuditDiffTable>
   */
  private $diffColumns;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param array[] $config         The content of the configuration file.
   * @param array[] $configMetadata The content of the metadata file.
   */
  public function __construct(&$config, $configMetadata)
  {
    $this->config         = &$config;
    $this->configMetadata = $configMetadata;
    $this->codeStore      = new MySqlAlterTableCodeStore();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Create Sql statement for alter table.
   *
   * @param string               $tableName The table name.
   * @param TableColumnsMetadata $columns   Columns metadata for alter statement.
   */
  public function createSqlStatement($tableName, $columns)
  {
    $editCharSet = false;
    $charSet     = '';
    $this->codeStore->append(sprintf('ALTER TABLE %s.`%s` CHANGE', $this->config['database']['audit_schema'], $tableName));
    $countMax = $columns->getNumberOfColumns();
    $count    = 1;
    /** @var MultiSourceColumnMetadata $rowMetadata */
    foreach ($columns->getColumns() as $columnName => $rowMetadata)
    {
      $columnProperties = $rowMetadata->getProperties();
      /** @var ColumnMetadata $data */
      $data = isset($columnProperties['data']) ? $columnProperties['data'] : null;
      /** @var ColumnMetadata $config */
      $config = isset($columnProperties['config']) ? $columnProperties['config'] : null;

      $dataMetadata   = isset($data) ? $data->getProperties() : null;
      $configMetadata = isset($config) ? $config->getProperties() : null;

      if (!isset($dataMetadata))
      {
        if (isset($configMetadata['character_set_name']))
        {
          $editCharSet = true;
          $charSet     = $configMetadata['character_set_name'];
        }
        $line = sprintf('`%s` `%s` %s', $columnName, $columnName, $configMetadata['column_type']);
        if ($count!=$countMax) $line .= ',';
        $this->codeStore->append($line);
      }
      else
      {
        if (isset($dataMetadata['character_set_name']))
        {
          $editCharSet = true;
          $charSet     = $dataMetadata['character_set_name'];
        }
        $line = sprintf('`%s` `%s` %s', $columnName, $columnName, $dataMetadata['column_type']);
        if ($count!=$countMax) $line .= ',';
        $this->codeStore->append($line);
      }
      $count++;
    }
    $this->codeStore->append(';');
    if ($editCharSet)
    {
      $this->codeStore->append(sprintf('ALTER TABLE %s.`%s` DEFAULT CHARACTER SET %s;', $this->config['database']['audit_schema'], $tableName, $charSet));
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The main method: executes the create alter table statement actions for tables.
   *
   * return string
   */
  public function main()
  {
    $this->processData();

    return $this->codeStore->getCode();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Computes the difference between data and audit tables.
   */
  private function getDiff()
  {
    foreach ($this->dataSchemaTables as $table)
    {
      if ($this->config['tables'][$table['table_name']]['audit'])
      {
        $res = AuditDataLayer::searchInRowSet('table_name', $table['table_name'], $this->auditSchemaTables);
        if (isset($res))
        {
          $this->diffColumns[$table['table_name']] = new AuditDiffTable($this->config['database']['data_schema'],
                                                                        $this->config['database']['audit_schema'],
                                                                        $table['table_name'],
                                                                        $this->auditColumnsMetadata,
                                                                        $this->configMetadata[$table['table_name']]);
        }
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Getting list of all tables from information_schema of database from config file.
   */
  private function listOfTables()
  {
    $this->dataSchemaTables = AuditDataLayer::getTablesNames($this->config['database']['data_schema']);

    $this->auditSchemaTables = AuditDataLayer::getTablesNames($this->config['database']['audit_schema']);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   *  Work on data for each table.
   */
  private function processData()
  {
    $this->resolveCanonicalAuditColumns();

    $this->listOfTables();

    $this->getDiff();

    /** @var AuditDiffTable $diffTable */
    foreach ($this->diffColumns as $tableName => $diffTable)
    {
      // Remove matching columns.
      $columns = $diffTable->removeMatchingColumns(true);

      $this->createSqlStatement($tableName, $columns);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Resolves the canonical column types of the audit table columns.
   */
  private function resolveCanonicalAuditColumns()
  {
    if (empty($this->config['audit_columns']))
    {
      $this->auditColumnsMetadata = [];
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

        if ($columns[$key]['is_nullable']==='NO')
        {
          $columns[$key]['column_type'] = sprintf('%s not null', $columns[$key]['column_type']);
        }
        if (isset($audit_column['value_type']))
        {
          $columns[$key]['value_type'] = $audit_column['value_type'];
        }
        if (isset($audit_column['expression']))
        {
          $columns[$key]['expression'] = $audit_column['expression'];
        }
      }

      $this->auditColumnsMetadata = $columns;
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
