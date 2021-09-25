<?php
declare(strict_types=1);

namespace SetBased\Audit\Audit;

use SetBased\Audit\AuditTable;
use SetBased\Audit\Metadata\TableColumnsMetadata;
use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Audit\Style\AuditStyle;
use SetBased\Config\TypedConfig;
use SetBased\Stratum\Middle\Helper\RowSetHelper;

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
  private TableColumnsMetadata $additionalAuditColumns;

  /**
   * The names of all tables in audit schema.
   *
   * @var array
   */
  private array $auditSchemaTables;

  /**
   * The strong typed configuration reader and writer.
   *
   * @var TypedConfig
   */
  private TypedConfig $config;

  /**
   * The names of all tables in data schema.
   *
   * @var array
   */
  private array $dataSchemaTables;

  /**
   * The Output decorator.
   *
   * @var AuditStyle
   */
  private AuditStyle $io;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param TypedConfig $config The strong typed configuration reader and writer.
   * @param AuditStyle  $io     The Output decorator.
   */
  public function __construct(TypedConfig $config, AuditStyle $io)
  {
    $this->config = $config;
    $this->io     = $io;

    $this->additionalAuditColumns =
      AuditDataLayer::$dl->resolveCanonicalAdditionalAuditColumns($this->config->getManString('database.audit_schema'),
                                                                  $this->config->getManArray('audit_columns'));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Getting list of all tables from information_schema of database from config file.
   */
  public function listOfTables(): void
  {
    $this->dataSchemaTables  = AuditDataLayer::$dl->getTablesNames($this->config->getManString('database.data_schema'));
    $this->auditSchemaTables = AuditDataLayer::$dl->getTablesNames($this->config->getManString('database.audit_schema'));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The main method: executes the auditing actions for tables.
   */
  public function main(): void
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
  public function obsoleteTables(): void
  {
    foreach ($this->config->getManArray('tables') as $tableName => $dummy)
    {
      if (RowSetHelper::searchInRowSet($this->dataSchemaTables, 'table_name', $tableName)===null)
      {
        $this->io->writeln(sprintf('<info>Removing obsolete table %s from config file</info>', $tableName));

        // Unset table (work a round bug in \Noodlehaus\Config::remove()).
        $config = $this->config->getConfig();
        $tables = $config['tables'];
        unset($tables[$tableName]);
        $config->set('tables', $tables);
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Compares the tables listed in the config file and the tables found in the data schema.
   */
  public function unknownTables(): void
  {
    foreach ($this->dataSchemaTables as $table)
    {
      if ($this->config->getOptArray('tables.'.$table['table_name'])!==null)
      {
        if ($this->config->getOptBool('tables.'.$table['table_name'].'.audit')===null)
        {
          $this->io->writeln(sprintf('<info>Audit not set for table %s</info>', $table['table_name']));
        }
        else
        {
          if ($this->config->getManBool('tables.'.$table['table_name'].'.audit'))
          {
            if ($this->config->getOptString('tables.'.$table['table_name'].'.alias')===null)
            {
              $this->config->getConfig()->set('tables.'.$table['table_name'].'.alias', AuditTable::getRandomAlias());
            }
          }
        }
      }
      else
      {
        $this->io->writeln(sprintf('<info>Found new table %s</info>', $table['table_name']));
        $config = $this->config->getConfig();
        $config->set('tables.'.$table['table_name'], ['audit' => null, 'alias' => null, 'skip' => null]);
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Processed known tables.
   */
  private function knownTables(): void
  {
    foreach ($this->dataSchemaTables as $table)
    {
      $audit = $this->config->getOptBool('tables.'.$table['table_name'].'.audit');
      if ($audit===true)
      {
        $currentTable = new AuditTable($this->io,
                                       $this->config->getManString('database.data_schema'),
                                       $this->config->getManString('database.audit_schema'),
                                       $table['table_name'],
                                       $this->additionalAuditColumns,
                                       $this->config->getOptString('tables.'.$table['table_name'].'.alias'),
                                       $this->config->getOptString('tables.'.$table['table_name'].'.skip'));

        // Ensure the audit table exists.
        if (RowSetHelper::searchInRowSet($this->auditSchemaTables, 'table_name', $table['table_name'])===null)
        {
          $currentTable->createAuditTable();
        }

        // Drop and create audit triggers and add new columns to the audit table.
        $currentTable->main($this->config->getManArray('additional_sql'));
      }
      elseif ($audit===false)
      {
        AuditTable::dropAuditTriggers($this->io,
                                      $this->config->getManString('database.data_schema'),
                                      $table['table_name']);
      }
      else /* $audit===null */
      {
        $this->io->logVerbose('Ignoring table <dbo>%s</dbo>', $table['table_name']);
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
