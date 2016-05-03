<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\Audit\Command;

use SetBased\Audit\Columns;
use SetBased\Audit\MySql\Command\AuditCommand;
use SetBased\Audit\MySql\DataLayer;
use SetBased\Stratum\MySql\StaticDataLayer;
use SetBased\Stratum\Style\StratumStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

//----------------------------------------------------------------------------------------------------------------------
/**
 * Command for comparing data tables with audit tables.
 */
class DiffCommand extends AuditCommand
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * With this option all tables and columns are shown.
   *
   * @var string
   */
  private $option;

  /**
   * Array with columns for each table.
   * array [
   *    table_name [
   *            column [
   *                    data table type,
   *                    audit table type
   *                    ],
   *                      ...
   *               ]
   *       ]
   *
   * @var array[]
   */
  private $diffColumns;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this->setName('diff')
         ->setDescription('Compares data tables and audit tables')
         ->addArgument('config file', InputArgument::OPTIONAL, 'The audit configuration file', 'etc/audit.json')
         ->addOption('full', 'f', InputArgument::OPTIONAL, 'Show all columns');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->io = new StratumStyle($input, $output);

    $this->configFileName = $input->getArgument('config file');
    $this->readConfigFile();

    $this->option = $input->getOption('full');

    // Create database connection with params from config file
    $this->connect($this->config);

    $this->listOfTables();

    $this->getDiff();
    $this->printDiff($output);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Get the difference between data and audit tables.
   *
   * @param OutputInterface $output
   */
  private function printDiff(OutputInterface $output)
  {
    foreach ($this->diffColumns as $tableName => $columns)
    {
      $columns = $this->checkFullOption($columns);
      if (!empty($columns))
      {
        $columns = $this->addHighlighting($columns);
        $table   = new Table($output);
        $table
          ->setHeaders(['column name', 'data table type', 'audit table type'])
          ->setRows($columns);
        $output->writeln(sprintf('<info>%s</info>', $tableName));
        $table->render();
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Check full option and return array without new or obsolete columns if option not set.
   *
   * @param $theColumns
   *
   * @return array[]
   */
  private function checkFullOption($theColumns)
  {
    $notFullColumns = [];
    if (!isset($this->option))
    {
      foreach ($theColumns as $column)
      {
        if (strcmp($column['data_table_type'], $column['audit_table_type']))
        {
          $notFullColumns[] = $column;
        }
      }
    }
    else
    {
      $notFullColumns = $theColumns;
    }

    return $notFullColumns;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Add highlighting to columns.
   *
   * @param $theColumns
   *
   * @return array[]
   */
  private function addHighlighting($theColumns)
  {
    $styledColumns = [];
    foreach ($theColumns as $column)
    {
      $styledColumn = $column;
      if (isset($column['data_table_type']) && !isset($column['audit_table_type']))
      {
        $styledColumn['column_name']     = sprintf('<fg=red>%s</>', $styledColumn['column_name']);
        $styledColumn['data_table_type'] = sprintf('<fg=yellow>%s</>', $styledColumn['data_table_type']);
      }
      else if (!isset($column['data_table_type']) && isset($column['audit_table_type']))
      {
        $styledColumn['audit_table_type'] = sprintf('<fg=yellow>%s</>', $styledColumn['audit_table_type']);
      }
      else if (strcmp($column['data_table_type'], $column['audit_table_type']))
      {
        $styledColumn['column_name']      = sprintf('<fg=red>%s</>', $styledColumn['column_name']);
        $styledColumn['data_table_type']  = sprintf('<fg=yellow>%s</>', $styledColumn['data_table_type']);
        $styledColumn['audit_table_type'] = sprintf('<fg=yellow>%s</>', $styledColumn['audit_table_type']);
      }
      $styledColumns[] = $styledColumn;
    }

    return $styledColumns;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Get the difference between data and audit tables.
   */
  private function getDiff()
  {
    foreach ($this->dataSchemaTables as $table)
    {
      if ($this->config['tables'][$table['table_name']]['audit'])
      {
        $res = StaticDataLayer::searchInRowSet('table_name', $table['table_name'], $this->auditSchemaTables);
        if (isset($res))
        {
          $dataColumns  = new Columns(DataLayer::getTableColumns($this->config['database']['data_schema'], $table['table_name']));
          $auditColumns = DataLayer::getTableColumns($this->config['database']['audit_schema'], $table['table_name']);

          // Removing audit columns.
          $auditColumns = array_udiff($auditColumns, $this->config['audit_columns'], '\SetBased\Audit\Command\DiffCommand::udiffCompare');
          $auditColumns = new Columns($auditColumns);

          $this->diffColumns[$table['table_name']] = $this->createDiffArray($dataColumns, $auditColumns);
        }
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Get the difference between data and audit tables.
   *
   * @param Columns $theDataColumns  The table columns from data schema.
   * @param Columns $theAuditColumns The table columns from audit schema.
   *
   * @return array[]
   */
  private function createDiffArray($theDataColumns, $theAuditColumns)
  {
    $columnsForTable = [];
    foreach ($theDataColumns->getColumns() as $column)
    {
      $columnsForTable[$column['column_name']] = ['column_name'      => $column['column_name'],
                                                  'data_table_type'  => $column['column_type'],
                                                  'audit_table_type' => null];
    }

    foreach ($theAuditColumns->getColumns() as $column)
    {
      $columnsForTable[$column['column_name']] = ['column_name'      => $column['column_name'],
                                                  'data_table_type'  => isset($columnsForTable[$column['column_name']]) ? $columnsForTable[$column['column_name']]['data_table_type'] : null,
                                                  'audit_table_type' => $column['column_type']];
    }

    return $columnsForTable;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Helper function for compare two multidimensional arrays by column_name.
   *
   * @param $a
   * @param $b
   *
   * @return int
   */
  private function udiffCompare($a, $b)
  {
    return strcmp($a['column_name'], $b['column_name']);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
