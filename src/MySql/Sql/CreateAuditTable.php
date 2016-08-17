<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\Audit\MySql\Sql;

use SetBased\Audit\MySql\DataLayer;
use SetBased\Audit\MySql\Metadata\ColumnMetadata;
use SetBased\Audit\MySql\Metadata\TableColumnsMetadata;
use SetBased\Helper\CodeStore\MySqlCompoundSyntaxCodeStore;

//----------------------------------------------------------------------------------------------------------------------
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
  private $auditSchemaName;

  /**
   * The name of the table.
   *
   * @var TableColumnsMetadata
   */
  private $columns;

  /**
   * The name of the data schema.
   *
   * @var string
   */
  private $dataSchemaName;

  /**
   * The name of the table.
   *
   * @var string
   */
  private $tableName;

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
  public function __construct($dataSchemaName,
                              $auditSchemaName,
                              $tableName,
                              $columns)
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
  public function buildStatement()
  {
    $code = new MySqlCompoundSyntaxCodeStore();

    $code->append(sprintf('create table `%s`.`%s`', $this->auditSchemaName, $this->tableName));

    // Base format on column with longest name.
    $columns = $this->columns->getColumns();
    $width   = 0;
    /** @var ColumnMetadata $column */
    foreach ($columns as $column)
    {
      $width = max($width, mb_strlen($column->getProperty('column_name')));
    }
    $format = sprintf('  %%-%ds %%s', $width + 2);

    // Create SQL for columns.
    $code->append('(');
    foreach ($columns as $column)
    {
      $code->append(sprintf($format, '`'.$column->getProperty('column_name').'`', $column->getProperty('column_type')), false);
      if (end($columns)!==$column)
      {
        $code->appendToLastLine(',');
      }
    }

    // Create SQL for table options.
    $tableOptions = DataLayer::getTableOptions($this->dataSchemaName, $this->tableName);
    $code->append(sprintf(') engine=%s character set=%s collate=%s',
                          $tableOptions['engine'],
                          $tableOptions['character_set_name'],
                          $tableOptions['table_collation']));

    return $code->getCode();
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
