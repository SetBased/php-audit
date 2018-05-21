<?php

namespace SetBased\Audit\MySql;

use SetBased\Audit\MySql\Helper\DiffTableHelper;
use SetBased\Exception\FallenException;
use SetBased\Stratum\Style\StratumStyle;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class for executing auditing actions for tables.
 */
class AuditDiff
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The metadata (additional) audit columns (as stored in the config file).
   *
   * @var array<integer,object<array>>
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

  /**
   * If set all tables and columns are shown.
   *
   * @var boolean
   */
  private $full;

  /**
   * The Input interface.
   *
   * @var InputInterface
   */
  private $input;

  /**
   * The Output decorator.
   *
   * @var StratumStyle
   */
  private $io;

  /**
   * The Output interface.
   *
   * @var OutputInterface
   */
  private $output;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param array[]         $config         The content of the configuration file.
   * @param array[]         $configMetadata The content of the metadata file.
   * @param StratumStyle    $io             The Output decorator.
   * @param InputInterface  $input
   * @param OutputInterface $output
   */
  public function __construct(&$config, $configMetadata, $io, $input, $output)
  {
    $this->io             = $io;
    $this->config         = &$config;
    $this->configMetadata = $configMetadata;
    $this->input          = $input;
    $this->output         = $output;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The main method: executes the auditing actions for tables.
   */
  public function main()
  {
    // Style for column names with miss matched column types.
    $style = new OutputFormatterStyle(null, 'red');
    $this->output->getFormatter()->setStyle('mm_column', $style);

    // Style for column types of columns with miss matched column types.
    $style = new OutputFormatterStyle('yellow');
    $this->output->getFormatter()->setStyle('mm_type', $style);

    // Style for obsolete tables.
    $style = new OutputFormatterStyle('yellow');
    $this->output->getFormatter()->setStyle('obsolete_table', $style);

    // Style for missing tables.
    $style = new OutputFormatterStyle('red');
    $this->output->getFormatter()->setStyle('miss_table', $style);

    $this->full = $this->input->getOption('full');

    $this->resolveCanonicalAuditColumns();

    $this->listOfTables();

    $this->getDiff();

    $this->printDiff();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Writes the difference between the audit tables and metadata tables to the output.
   */
  private function diffTables()
  {
    $missTables     = [];
    $obsoleteTables = [];
    foreach ($this->config['tables'] as $tableName => $table)
    {
      $res = AuditDataLayer::searchInRowSet('table_name', $tableName, $this->auditSchemaTables);
      if ($table['audit'] && !isset($res))
      {
        $missTables[] = $tableName;
      }
      else if (!$table['audit'] && isset($res))
      {
        $obsoleteTables[] = $tableName;
      }
    }
    $this->printMissObsoleteTables('missing', $missTables);
    $this->printMissObsoleteTables('obsolete', $obsoleteTables);
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
   * Writes the difference between the audit and data tables to the output.
   */
  private function printDiff()
  {
    $first = true;
    if (isset($this->diffColumns))
    {
      /** @var AuditDiffTable $diffTable */
      foreach ($this->diffColumns as $tableName => $diffTable)
      {
        $columns = $diffTable->getDiffColumns();
        // Remove matching columns unless the full option is used.
        if (!$this->full)
        {
          $columns = $diffTable->removeMatchingColumns(false);
        }

        if ($columns->getNumberOfColumns()>0)
        {
          // Add an empty line between tables.
          if ($first)
          {
            $first = false;
          }
          else
          {
            $this->output->writeln('');
          }

          // Write table name.
          $this->output->writeln($tableName);

          // Write table with columns.
          $rows = new DiffTableHelper($this->config['database']['data_schema'],
                                      $this->config['database']['audit_schema'],
                                      $tableName,
                                      $this->auditColumnsMetadata,
                                      $this->full);
          $rows->appendRows($columns);
          $rows->addHighlighting();
          $table = new Table($this->output);
          $table->setHeaders(['column', 'data table', 'audit table', 'config'])
                ->setRows($rows->getRows());
          $table->render();
        }
      }
      $this->diffTables();
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Print missing or obsolete tables;
   *
   * @param string  $tableType Missing or obsolete.
   * @param array[] $tables    Table names array.
   */
  private function printMissObsoleteTables($tableType, $tables)
  {
    if (!empty($tables))
    {
      switch ($tableType)
      {
        case 'missing':
          $tag = 'miss_table';
          $this->output->writeln('<miss_table>Missing Tables:</>');
          break;

        case 'obsolete':
          $tag = 'obsolete_table';
          $this->output->writeln('<obsolete_table>Obsolete Tables:</>');
          break;

        default:
          throw new FallenException('table type', $tableType);
      }

      foreach ($tables as $tableName)
      {
        $this->output->writeln(sprintf('<%s>%s</>', $tag, $tableName));
      }
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
