<?php
declare(strict_types=1);

namespace SetBased\Audit\MySql\Sql;

use SetBased\Audit\Metadata\TableColumnsMetadata;
use SetBased\Helper\CodeStore\MySqlCompoundSyntaxCodeStore;

/**
 * Class for creating SQL statements for adding new columns to an audit table.
 */
class AlterAuditTableAddColumns
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The name of the audit schema.
   *
   * @var string
   */
  private string $auditSchemaName;

  /**
   * The array of new columns for adding to table.
   *
   * @var TableColumnsMetadata
   */
  private TableColumnsMetadata $columns;

  /**
   * The name of the audit table.
   *
   * @var string
   */
  private string $tableName;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param string               $auditSchemaName The name of the audit schema.
   * @param string               $tableName       The name of the table.
   * @param TableColumnsMetadata $columns         The metadata of the new columns of the audit table (i.e. the audit
   *                                              columns and columns of the data table).
   */
  public function __construct(string $auditSchemaName, string $tableName, TableColumnsMetadata $columns)
  {
    $this->auditSchemaName = $auditSchemaName;
    $this->tableName       = $tableName;
    $this->columns         = $columns;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns a SQL statement for adding new columns to the audit table.
   *
   * @return string
   */
  public function buildStatement(): string
  {
    $code = new MySqlCompoundSyntaxCodeStore();

    $code->append(sprintf('alter table `%s`.`%s`', $this->auditSchemaName, $this->tableName));
    foreach ($this->columns->getColumns() as $column)
    {
      $code->append(sprintf('  add `%s` %s', $column->getName(), $column->getColumnAuditDefinition()), false);
      $after = $column->getProperty('after');
      if (isset($after))
      {
        $code->appendToLastLine(sprintf(' after `%s`', $after));
      }
      else
      {
        $code->appendToLastLine(' first');
      }
      $columns = $this->columns->getColumns();
      if (end($columns)!==$column)
      {
        $code->appendToLastLine(',');
      }
    }

    return $code->getCode();
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
