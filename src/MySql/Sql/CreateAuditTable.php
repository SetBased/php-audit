<?php

namespace SetBased\Audit\MySql\Sql;

use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Audit\MySql\Metadata\TableColumnsMetadata;
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

    // Create SQL for columns.
    $code->append('(');
    $code->append($this->getColumnDefinitions());

    // Create SQL for table options.
    $tableOptions = AuditDataLayer::getTableOptions($this->dataSchemaName, $this->tableName);
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
  private function getColumnDefinitions()
  {
    $lines = [];

    // Base format on column with longest name.
    $format = $this->getFormat();

    $columns = $this->columns->getColumns();
    foreach ($columns as $column)
    {
      $line = sprintf($format, '`'.$column->getProperty('column_name').'`', $column->getProperty('column_type'));

      // Timestamps require special settings for null values.
      if ($column->getProperty('column_type')=='timestamp')
      {
        $line .= ' null';

        if ($column->getProperty('is_nullable')=='YES')
        {
          $line .= ' default null';
        }
      }

      // Set character set and collation.
      if ($column->getProperty('character_set_name'))
      {
        $line .= ' character set ';
        $line .= $column->getProperty('character_set_name');
      }
      if ($column->getProperty('collation_name'))
      {
        $line .= ' collate ';
        $line .= $column->getProperty('collation_name');
      }

      // Set nullable.
      if ($column->getProperty('is_nullable')=='NO')
      {
        $line .= ' not null';
      }

      if (end($columns)!==$column)
      {
        $line .= ',';
      }

      $lines[] = $line;
    }

    return $lines;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the format specifier for printing column names and column types
   *
   * @return string
   */
  private function getFormat()
  {
    $width = 0;
    foreach ($this->columns->getColumns() as $column)
    {
      $width = max($width, mb_strlen($column->getProperty('column_name')));
    }

    return sprintf('  %%-%ds %%s', $width + 2);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
