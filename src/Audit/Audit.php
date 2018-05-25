<?php

namespace SetBased\Audit\Audit;

use SetBased\Audit\AuditTable;
use SetBased\Audit\Metadata\TableColumnsMetadata;
use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Stratum\Style\StratumStyle;

/**
 * Class for executing auditing actions for tables.
 */
class Audit
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The metadata of the additional audit columns.
   *
   * @var TableColumnsMetadata
   */
  private $additionalAuditColumns;

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

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param array[]      $config The content of the configuration file.
   * @param StratumStyle $io     The Output decorator.
   */
  public function __construct(&$config, $io)
  {
    $this->config = &$config;
    $this->io     = $io;

    $this->additionalAuditColumns =
      AuditDataLayer::resolveCanonicalAdditionalAuditColumns($this->config['database']['audit_schema'],
                                                             $this->config['audit_columns']);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Getting list of all tables from information_schema of database from config file.
   */
  public function listOfTables()
  {
    $this->dataSchemaTables  = AuditDataLayer::getTablesNames($this->config['database']['data_schema']);
    $this->auditSchemaTables = AuditDataLayer::getTablesNames($this->config['database']['audit_schema']);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The main method: executes the auditing actions for tables.
   */
  public function main()
  {
    $this->listOfTables();

    $this->unknownTables();

    $this->obsoleteTables();

    $this->knownTables();
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
   * Processed known tables.
   */
  private function knownTables()
  {
    foreach ($this->dataSchemaTables as $table)
    {
      if ($this->config['tables'][$table['table_name']]['audit'])
      {
        $currentTable = new AuditTable($this->io,
                                       $this->config['database']['data_schema'],
                                       $this->config['database']['audit_schema'],
                                       $table['table_name'],
                                       $this->additionalAuditColumns,
                                       $this->config['tables'][$table['table_name']]['alias'],
                                       $this->config['tables'][$table['table_name']]['skip']);

        // Ensure the audit table exists.
        if (AuditDataLayer::searchInRowSet('table_name', $table['table_name'], $this->auditSchemaTables)===null)
        {
          $currentTable->createAuditTable();
        }

        // Drop and create audit triggers and add new columns to the audit table.
        $currentTable->main($this->config['additional_sql']);
      }
      else
      {
        AuditTable::dropAuditTriggers($this->io, $this->config['database']['data_schema'], $table['table_name']);
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
