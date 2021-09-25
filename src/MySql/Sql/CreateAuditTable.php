<?php
declare(strict_types=1);

namespace SetBased\Audit\MySql\Sql;

use SetBased\Audit\Metadata\TableColumnsMetadata;
use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Helper\CodeStore\MySqlCompoundSyntaxCodeStore;

/**
 * Class for creating SQL statements for creating audit tables.
 */
class CreateAuditTable
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The name of the audit schema.
   *
   * @var string
   */
  private string $auditSchemaName;

  /**
   * The name of the table.
   *
   * @var TableColumnsMetadata
   */
  private TableColumnsMetadata $columns;

  /**
   * The name of the data schema.
   *
   * @var string
   */
  private string $dataSchemaName;

  /**
   * The name of the table.
   *
   * @var string
   */
  private string $tableName;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param string               $dataSchemaName  The name of the data schema.
   * @param string               $auditSchemaName The name of the audit schema.
   * @param string               $tableName       The name of the table.
   * @param TableColumnsMetadata $columns         The metadata of the columns of the audit table (i.e. the audit
   *                                              columns and columns of the data table).
   */
  public function __construct(string $dataSchemaName,
                              string $auditSchemaName,
                              string $tableName,
                              TableColumnsMetadata $columns)
  {
    $this->dataSchemaName  = $dataSchemaName;
    $this->auditSchemaName = $auditSchemaName;
    $this->tableName       = $tableName;
    $this->columns         = $columns;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns a SQL statement for creating the audit table.
   *
   * @return string
   */
  public function buildStatement(): string
  {
    $code = new MySqlCompoundSyntaxCodeStore();

    $code->append(sprintf('create table `%s`.`%s`', $this->auditSchemaName, $this->tableName));

    // Create SQL for columns.
    $code->append('(');
    $code->append($this->getColumnDefinitions());

    // Create SQL for table options.
    $tableOptions = AuditDataLayer::$dl->getTableOptions($this->dataSchemaName, $this->tableName);
    $code->append(sprintf(') engine=%s character set=%s collate=%s',
                          $tableOptions['engine'],
                          $tableOptions['character_set_name'],
                          $tableOptions['table_collation']));

    return $code->getCode();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns an array with SQL code for column definitions.
   *
   * @return string[]
   */
  private function getColumnDefinitions(): array
  {
    $lines = [];

    $columns   = $this->columns->getColumns();
    $maxLength = $this->columns->getLongestColumnNameLength();
    foreach ($columns as $column)
    {
      $name   = $column->getName();
      $filler = str_repeat(' ', $maxLength - mb_strlen($name) + 1);

      $line = sprintf('  `%s`%s%s', $name, $filler, $column->getColumnAuditDefinition());

      if (end($columns)!==$column)
      {
        $line .= ',';
      }

      $lines[] = $line;
    }

    return $lines;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
