<?php

namespace SetBased\Audit\MySql\Metadata;

//----------------------------------------------------------------------------------------------------------------------
use SetBased\Exception\FallenException;

/**
 * Metadata of a set of table columns.
 */
class TableColumnsMetadata
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The metadata of the columns.
   *
   * @var ColumnMetadata[]
   */
  private $columns = [];

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param array[] $columns The metadata of the columns as returned by AuditDataLayer::getTableColumns().
   * @param string   $type    The class for columns metadata.
   */
  public function __construct($columns = [], $type = 'ColumnMetadata')
  {
    foreach ($columns as $columnName => $column)
    {
      $this->columns[$column['column_name']] = self::columnFactory($type, $column);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Combines the metadata of two sets of metadata of table columns.
   *
   * @param TableColumnsMetadata $columns1 The first set of metadata of table columns.
   * @param TableColumnsMetadata $columns2 The second set of metadata of table columns.
   *
   * @return TableColumnsMetadata
   */
  public static function combine($columns1, $columns2)
  {
    $columns = new TableColumnsMetadata();

    foreach ($columns1->columns as $column)
    {
      $columns->appendTableColumn($column);
    }

    foreach ($columns2->columns as $column)
    {
      $columns->appendTableColumn($column);
    }

    return $columns;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Compares two sets of metadata of table columns and returns a set of metadata of table columns the are in both sets
   * but have different column type.
   *
   * @param TableColumnsMetadata $columns1 The first sets of metadata of table columns.
   * @param TableColumnsMetadata $columns2 The second sets of metadata of table columns.
   * @param string[]             $ignore   The properties to be ignored.
   *
   * @return TableColumnsMetadata
   */
  public static function differentColumnTypes($columns1, $columns2, $ignore = [])
  {
    $diff = new TableColumnsMetadata();
    foreach ($columns1->columns as $column_name => $column1)
    {
      if (isset($columns2->columns[$column_name]))
      {
        if (!ColumnMetadata::compare($column1, $columns2->columns[$column_name], $ignore))
        {
          $diff->appendTableColumn($column1);
        }
      }
    }

    return $diff;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Compares two sets of metadata of table columns and returns a set of metadata of table columns that are in the first
   * sets of metadata of table columns but not in the second sets of metadata of table columns.
   *
   * @param TableColumnsMetadata $columns1 The first sets of metadata of table columns.
   * @param TableColumnsMetadata $columns2 The second sets of metadata of table columns.
   *
   * @return TableColumnsMetadata
   */
  public static function notInOtherSet($columns1, $columns2)
  {
    $diff = new TableColumnsMetadata();
    foreach ($columns1->columns as $column_name => $column1)
    {
      if (!isset($columns2->columns[$column_name]))
      {
        $diff->appendTableColumn($column1);
      }
    }

    return $diff;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * A factory for table column metadata.
   *
   * @param string $type   The type of the metadata.
   * @param array  $column The metadata of the column
   *
   * @return MultiSourceColumnMetadata|AlterColumnMetadata|AuditColumnMetadata|ColumnMetadata
   */
  private static function columnFactory($type, $column)
  {
    switch ($type)
    {
      case 'ColumnMetadata':
        return new ColumnMetadata($column);

      case 'AlterColumnMetadata':
        return new AlterColumnMetadata($column);

      case 'AuditColumnMetadata':
        return new AuditColumnMetadata($column);

      case 'MultiSourceColumnMetadata':
        return new MultiSourceColumnMetadata($column);

      default:
        throw new FallenException('type', $type);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Appends a table column to the table columns.
   *
   * @param ColumnMetadata $column The metadata of the table columns.
   */
  public function appendTableColumn($column)
  {
    $this->columns[$column->getProperty('column_name')] = $column;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the underlying array with metadata of the columns.
   *
   * @return ColumnMetadata[]
   */
  public function getColumns()
  {
    return $this->columns;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the number of columns.
   *
   * @return int
   */
  public function getNumberOfColumns()
  {
    return count($this->columns);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns previous column of a columns. Returns null if the column name is not found in this TableColumnsMetadata.
   *
   * @param string $columnName The column name.
   *
   * @return int|null
   */
  public function getPreviousColumn($columnName)
  {
    $columns = array_keys($this->columns);
    $key     = array_search($columnName, $columns);

    if ($key>=1)
    {
      return $columns[$key - 1];
    }

    return null;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Makes all columns nullable.
   */
  public function makeNullable()
  {
    foreach ($this->columns as $column)
    {
      $column->makeNullable();
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Remove column.
   *
   * @param string $columnName The columns name.
   */
  public function removeColumn($columnName)
  {
    unset($this->columns[$columnName]);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
