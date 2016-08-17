<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\Audit\MySql;

use SetBased\Audit\MySql\Metadata\ColumnMetadata;
use SetBased\Audit\MySql\Metadata\TableColumnsMetadata;
use SetBased\Audit\MySql\Metadata\TableMetadata;
use SetBased\Stratum\MySql\StaticDataLayer;
use SetBased\Stratum\Style\StratumStyle;

//----------------------------------------------------------------------------------------------------------------------
/**
 * Class for audit actions.
 */
class Audit
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * All tables in the in the audit schema.
   *
   * @var array
   */
  protected $auditSchemaTables;

  /**
   * All config file as array.
   *
   * @var array
   */
  protected $config;

  /**
   * Array of tables from data schema.
   *
   * @var array
   */
  protected $dataSchemaTables;

  /**
   * The Output decorator.
   *
   * @var StratumStyle
   */
  protected $io;

  /**
   * If true remove all column information from config file.
   *
   * @var boolean
   */
  private $pruneOption;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param array[]      $config All config file as array
   * @param StratumStyle $io     The Output decorator
   */
  public function __construct(&$config, $io)
  {
    $this->config = &$config;
    $this->io     = $io;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Compares the tables listed in the config file and the tables found in the audit schema
   *
   * @param string               $tableName Name of table
   * @param TableColumnsMetadata $columns   The table columns.
   */
  public function getColumns($tableName, $columns)
  {
    $newColumns = [];
    /** @var ColumnMetadata $column */
    foreach ($columns->getColumns() as $column)
    {
      $newColumns[] = $column->getProperties();
    }
    $this->config['table_columns'][$tableName] = $newColumns;

    if ($this->pruneOption)
    {
      $this->config['table_columns'] = [];
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Getting list of all tables from information_schema of database from config file.
   */
  public function listOfTables()
  {
    $this->dataSchemaTables = DataLayer::getTablesNames($this->config['database']['data_schema']);

    $this->auditSchemaTables = DataLayer::getTablesNames($this->config['database']['audit_schema']);
  }

  //--------------------------------------------------------------------------------------------------------------------
  public function main()
  {
    $this->auditColumnTypes();

    $this->listOfTables();

    $this->unknownTables();

    foreach ($this->dataSchemaTables as $table)
    {
      if ($this->config['tables'][$table['table_name']]['audit'])
      {
        $tableColumns = [];
        if (isset($this->config['table_columns'][$table['table_name']]))
        {
          $tableColumns = $this->config['table_columns'][$table['table_name']];
        }
        $configTable          = new TableMetadata($table['table_name'], $this->config['database']['data_schema'], $tableColumns);
        $auditColumnsMetadata = new TableColumnsMetadata($this->config['audit_columns'], '\SetBased\Audit\MySql\Metadata\AuditColumnMetadata');
        $currentTable         = new AuditTable($this->io,
                                               $configTable,
                                               $this->config['database']['audit_schema'],
                                               $auditColumnsMetadata,
                                               $this->config['tables'][$table['table_name']]['alias'],
                                               $this->config['tables'][$table['table_name']]['skip']);
        $res                  = StaticDataLayer::searchInRowSet('table_name', $currentTable->getTableName(), $this->auditSchemaTables);
        if (!isset($res))
        {
          $currentTable->createMissingAuditTable();
        }

        $columns        = $currentTable->main($this->config['additional_sql']);
        $alteredColumns = $columns['altered_columns']->getColumns();
        if (empty($alteredColumns))
        {
          $this->getColumns($currentTable->getTableName(), $columns['full_columns']);
        }
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Found tables in config file
   *
   * Compares the tables listed in the config file and the tables found in the data schema.
   */
  public function unknownTables()
  {
    foreach ($this->dataSchemaTables as $table)
    {
      if (isset($this->config['tables'][$table['table_name']]))
      {
        if (!isset($this->config['tables'][$table['table_name']]['audit']))
        {
          $this->io->writeln(sprintf('<info>AuditApplication flag is not set in table %s</info>', $table['table_name']));
        }
        else
        {
          if ($this->config['tables'][$table['table_name']]['audit'])
          {
            if (!isset($this->config['tables'][$table['table_name']]['alias']))
            {
              $this->config['tables'][$table['table_name']]['alias'] = AuditTable::getRandomAlias();
            }
          }
        }
      }
      else
      {
        $this->io->writeln(sprintf('<info>Found new table %s</info>', $table['table_name']));
        $this->config['tables'][$table['table_name']] = ['audit' => false,
                                                         'alias' => null,
                                                         'skip'  => null];
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Resolves the canonical column types of the audit table columns.
   */
  protected function auditColumnTypes()
  {
    // Return immediately of there are no audit columns.
    if (empty($this->config['audit_columns'])) return;

    $schema    = $this->config['database']['audit_schema'];
    $tableName = 'TMP_'.uniqid();
    DataLayer::createTemporaryTable($schema, $tableName, $this->config['audit_columns']);
    $auditColumns = DataLayer::showColumns($schema, $tableName);
    foreach ($auditColumns as $column)
    {
      $key = StaticDataLayer::searchInRowSet('column_name', $column['Field'], $this->config['audit_columns']);
      if (isset($key))
      {
        $this->config['audit_columns'][$key]['column_type'] = $column['Type'];
        if ($column['Null']==='NO')
        {
          $this->config['audit_columns'][$key]['column_type'] = sprintf('%s not null', $this->config['audit_columns'][$key]['column_type']);
        }
      }
    }
    DataLayer::dropTemporaryTable($schema, $tableName);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
