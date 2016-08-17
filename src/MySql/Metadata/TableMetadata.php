<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\Audit\MySql\Metadata;

//--------------------------------------------------------------------------------------------------------------------
/**
 * Class for the metadata of a database table.
 */
class TableMetadata
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The metadata of the columns of this table.
   *
   * @var TableColumnsMetadata.
   */
  private $columns;

  /**
   * The name of the schema were the table is located.
   *
   * @var string
   */
  private $schemaName;

  /**
   * The name of this table.
   *
   * @var string
   */
  private $tableName;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param string  $tableName  The table name.
   * @param string  $schemaName The name of the schema were the table is located.
   * @param array[] $columns    The metadata of the columns of this table.
   */
  public function __construct($tableName,
                              $schemaName,
                              $columns)
  {
    $this->tableName  = $tableName;
    $this->columns    = new TableColumnsMetadata($columns);
    $this->schemaName = $schemaName;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns table columns.
   *
   * @return TableColumnsMetadata
   */
  public function getColumns()
  {
    return $this->columns;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the name of schema.
   *
   * @return string
   */
  public function getSchemaName()
  {
    return $this->schemaName;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the name of this table.
   *
   * @return string
   */
  public function getTableName()
  {
    return $this->tableName;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
