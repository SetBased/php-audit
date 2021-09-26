<?php
declare(strict_types=1);

namespace SetBased\Audit\Audit;

use SetBased\Audit\DiffTable;
use SetBased\Audit\Metadata\TableColumnsMetadata;
use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Audit\MySql\Metadata\TableMetadata;
use SetBased\Audit\Style\AuditStyle;
use SetBased\Config\TypedConfig;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class for executing auditing actions for tables.
 */
class Diff
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The metadata of the additional audit columns.
   *
   * @var TableColumnsMetadata
   */
  private TableColumnsMetadata $additionalAuditColumns;

  /**
   * The strong typed configuration reader and writer.
   *
   * @var TypedConfig
   */
  private TypedConfig $config;

  /**
   * The Input interface.
   *
   * @var InputInterface
   */
  private InputInterface $input;

  /**
   * The Output decorator.
   *
   * @var AuditStyle
   */
  private AuditStyle $io;

  /**
   * The Output interface.
   *
   * @var OutputInterface
   */
  private OutputInterface $output;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param TypedConfig     $config The strong typed configuration reader and writer.
   * @param AuditStyle      $io     The Output decorator.
   * @param InputInterface  $input
   * @param OutputInterface $output
   */
  public function __construct(TypedConfig $config, AuditStyle $io, InputInterface $input, OutputInterface $output)
  {
    $this->io     = $io;
    $this->config = $config;
    $this->input  = $input;
    $this->output = $output;

    $this->additionalAuditColumns =
      AuditDataLayer::$dl->resolveCanonicalAdditionalAuditColumns($this->config->getManString('database.audit_schema'),
                                                                  $this->config->getManArray('audit_columns'));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The main method: executes the auditing actions for tables.
   */
  public function main(): void
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

    $lists = $this->getTableLists();

    $this->currentAuditTables($lists['current']);

    $this->missingAuditTables($lists['missing']);

    $this->obsoleteAuditTables($lists['obsolete']);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Prints the difference between a data and and its audit table.
   *
   * @param string $tableName The table name.
   */
  private function currentAuditTable(string $tableName): void
  {
    $columns           = AuditDataLayer::$dl->getTableColumns($this->config->getManString('database.data_schema'),
                                                              $tableName);
    $dataTableColumns  = new TableColumnsMetadata($columns);
    $columns           = AuditDataLayer::$dl->getTableColumns($this->config->getManString('database.audit_schema'),
                                                              $tableName);
    $auditTableColumns = new TableColumnsMetadata($columns, 'AuditColumnMetadata');

    // In the audit table columns coming from the data table are always nullable.
    $dataTableColumns->makeNullable();
    $dataTableColumns->unsetDefaults();
    $dataTableColumns = TableColumnsMetadata::combine($this->additionalAuditColumns, $dataTableColumns);

    // In the audit table columns coming from the data table don't have defaults.
    foreach ($auditTableColumns->getColumns() as $column)
    {
      if (!in_array($column->getName(), $this->additionalAuditColumns->getColumnNames()))
      {
        $column->unsetDefault();
      }
    }

    $dataTableOptions  = AuditDataLayer::$dl->getTableOptions($this->config->getManString('database.data_schema'),
                                                              $tableName);
    $auditTableOptions = AuditDataLayer::$dl->getTableOptions($this->config->getManString('database.audit_schema'),
                                                              $tableName);

    $dataTable  = new TableMetadata($dataTableOptions, $dataTableColumns);
    $auditTable = new TableMetadata($auditTableOptions, $auditTableColumns);

    $helper = new DiffTable($dataTable, $auditTable);
    $helper->print($this->io, $this->input->getOption('full'));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Prints the difference between data and audit tables.
   *
   * @param string[] $tableNames The names of the current tables.
   */
  private function currentAuditTables(array $tableNames): void
  {
    foreach ($tableNames as $tableName)
    {
      $this->currentAuditTable($tableName);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the names of the tables that must be compared.
   *
   * @return array[]
   */
  private function getTableLists(): array
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

    return ['current'  => array_intersect($tables1, $tables2, $tables3),
            'obsolete' => array_diff($tables3, $tables1),
            'missing'  => array_diff($tables1, $tables3)];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Prints the missing audit tables.
   *
   * @param string[] $tableNames The names of the obsolete tables.
   */
  private function missingAuditTables(array $tableNames): void
  {
    if (empty($tableNames)) return;

    $this->io->title('Missing Audit Tables');
    $this->io->listing($tableNames);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Prints the obsolete audit tables.
   *
   * @param string[] $tableNames The names of the obsolete tables.
   */
  private function obsoleteAuditTables(array $tableNames): void
  {
    if (empty($tableNames) || !$this->input->getOption('full')) return;

    $this->io->title('Obsolete Audit Tables');
    $this->io->listing($tableNames);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
