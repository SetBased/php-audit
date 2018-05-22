<?php

namespace SetBased\Audit\MySql;

use SetBased\Audit\MySql\Metadata\TableColumnsMetadata;
use SetBased\Audit\MySql\Metadata\TableMetadata;
use SetBased\Stratum\Style\StratumStyle;

/**
 * Class for executing auditing actions for tables.
 */
class Audit
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The metadata (additional) audit columns (as stored in the config file).
   *
   * @var TableColumnsMetadata
   */
  private $auditColumnsMetadata;

  /**
   * The names of all tables in audit schema.
   *
   * @var array
   */
  private $auditSchemaTables;

  /**
   * The content of the configuration file.
   *
   * @var array
   */
  private $config;

  /**
   * Tables metadata from config file.
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
   * The Output decorator.
   *
   * @var StratumStyle
   */
  private $io;

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
   * @param array[]      $config         The content of the configuration file.
   * @param array[]      $configMetadata The content of the metadata file.
   * @param StratumStyle $io             The Output decorator.
   */
  public function __construct(&$config, &$configMetadata, $io)
  {
    $this->config         = &$config;
    $this->configMetadata = &$configMetadata;
    $this->io             = $io;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Getting list of all tables from information_schema of database from config file.
   */
  public function listOfTables()
  {
    $this->dataSchemaTables = AuditDataLayer::getTablesNames($this->config['database']['data_schema']);

    $this->auditSchemaTables = AuditDataLayer::getTablesNames($this->config['database']['audit_schema']);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The main method: executes the auditing actions for tables.
   *
   * @return int The exit status.
   */
  public function main()
  {
    if ($this->pruneOption)
    {
      $this->configMetadata = [];
    }

    $this->resolveCanonicalAuditColumns();

    $this->listOfTables();

    $this->unknownTables();

    $this->obsoleteTables();

    $status = $this->knownTables();

    return $status;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Removes tables listed in the config file that are not longer in the data schema from the config file.
   */
  public function obsoleteTables()
  {
    foreach ($this->config['tables'] as $tableName => $dummy)
    {
      if (AuditDataLayer::searchInRowSet('table_name', $tableName, $this->dataSchemaTables)===null)
      {
        $this->io->writeln(sprintf('<info>Removing obsolete table %s from config file</info>', $tableName));
        unset($this->config['tables'][$tableName]);
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Sets the columns metadata of a table in the configuration file.
   *
   * @param string               $tableName The name of table.
   * @param TableColumnsMetadata $columns   The metadata of the table columns.
   */
  public function setConfigTableColumns($tableName, $columns)
  {
    $newColumns = [];
    foreach ($columns->getColumns() as $column)
    {
      $newColumns[] = $column->getProperties();
    }
    $this->configMetadata[$tableName] = $newColumns;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
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
          $this->io->writeln(sprintf('<info>Audit not set for table %s</info>', $table['table_name']));
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
        $this->config['tables'][$table['table_name']] = ['audit' => null,
                                                         'alias' => null,
                                                         'skip'  => null];
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Resolves the canonical column types of the audit table columns.
   */
  protected function resolveCanonicalAuditColumns()
  {
    if (empty($this->config['audit_columns']))
    {
      $this->auditColumnsMetadata = new TableColumnsMetadata();
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
  /**
   * Processed known tables.
   *
   * @return int The exit status.
   */
  private function knownTables()
  {
    $status = 0;

    foreach ($this->dataSchemaTables as $table)
    {
      if ($this->config['tables'][$table['table_name']]['audit'])
      {
        if (isset($this->configMetadata[$table['table_name']]))
        {
          $tableColumns = $this->configMetadata[$table['table_name']];
        }
        else
        {
          $tableColumns = [];
        }

        $metadata    = AuditDataLayer::getTableOptions($this->config['database']['data_schema'], $table['table_name']);
        $configTable = new TableMetadata($metadata, $tableColumns);

        $currentTable = new AuditTable($this->io,
                                       $configTable,
                                       $this->config['database']['audit_schema'],
                                       $this->auditColumnsMetadata,
                                       $this->config['tables'][$table['table_name']]['alias'],
                                       $this->config['tables'][$table['table_name']]['skip']);

        // Ensure the audit table exists.
        if (AuditDataLayer::searchInRowSet('table_name', $table['table_name'], $this->auditSchemaTables)===null)
        {
          $currentTable->createAuditTable();
        }

        // Drop and create audit triggers and add new columns to the audit table.
        $ok = $currentTable->main($this->config['additional_sql']);
        if ($ok)
        {
          $columns = new TableColumnsMetadata(AuditDataLayer::getTableColumns($this->config['database']['data_schema'],
                                                                              $table['table_name']));
          $this->setConfigTableColumns($table['table_name'], $columns);
        }
        else
        {
          $status += 1;
        }
      }
      else
      {
        $metadata    = AuditDataLayer::getTableOptions($this->config['database']['data_schema'], $table['table_name']);
        $configTable = new TableMetadata($metadata, []);

        $currentTable = new AuditTable($this->io,
                                       $configTable,
                                       $this->config['database']['audit_schema'],
                                       $this->auditColumnsMetadata,
                                       '',
                                       '');

        $currentTable->dropAuditTriggers($this->config['database']['data_schema'], $table['table_name']);
      }
    }

    return $status;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
