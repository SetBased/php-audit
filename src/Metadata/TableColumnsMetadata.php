<?php
declare(strict_types=1);

namespace SetBased\Audit\Metadata;

use SetBased\Audit\MySql\Metadata\AlterColumnMetadata;
use SetBased\Audit\MySql\Metadata\AuditColumnMetadata;
use SetBased\Audit\MySql\Metadata\ColumnMetadata;
use SetBased\Exception\FallenException;

/**
 * Metadata of a list of table columns.
 */
class TableColumnsMetadata
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The metadata of the columns.
   *
   * @var ColumnMetadata[]
   */
  private array $columns = [];

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param array[] $columns The metadata of the columns as returned by AuditDataLayer::getTableColumns().
   * @param string  $type    The class for columns metadata.
   */
  public function __construct(array $columns = [], string $type = 'ColumnMetadata')
  {
    foreach ($columns as $column)
    {
      $this->columns[$column['column_name']] = static::columnFactory($type, $column);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Combines the metadata of two lists of table columns.
   *
   * @param TableColumnsMetadata $columns1 The first metadata of a list of table columns.
   * @param TableColumnsMetadata $columns2 The second metadata of a list of table columns.
   *
   * @return TableColumnsMetadata
   */
  public static function combine(TableColumnsMetadata $columns1, TableColumnsMetadata $columns2): TableColumnsMetadata
  {
    $columns = new TableColumnsMetadata();

    $columns->appendTableColumns($columns1);
    $columns->appendTableColumns($columns2);

    return $columns;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Compares two lists of table columns and returns a list of  table columns the are in both lists but have different
   * metadata
   *
   * @param TableColumnsMetadata $columns1 The first list of table columns.
   * @param TableColumnsMetadata $columns2 The second list of table columns.
   * @param string[]             $ignore   The properties to be ignored.
   *
   * @return TableColumnsMetadata
   */
  public static function differentColumnTypes(TableColumnsMetadata $columns1,
                                              TableColumnsMetadata $columns2,
                                              array $ignore = []): TableColumnsMetadata
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
   * Compares two lists of table columns and returns a list of table columns that are in the first list of table columns
   * but not in the second list of table columns.
   *
   * @param TableColumnsMetadata $columns1 The first list of table columns.
   * @param TableColumnsMetadata $columns2 The second list of table columns.
   *
   * @return TableColumnsMetadata
   */
  public static function notInOtherSet(TableColumnsMetadata $columns1,
                                       TableColumnsMetadata $columns2): TableColumnsMetadata
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
   * @return AlterColumnMetadata|AuditColumnMetadata|ColumnMetadata
   */
  private static function columnFactory(string $type, array $column): ColumnMetadata
  {
    switch ($type)
    {
      case 'ColumnMetadata':
        return new ColumnMetadata($column);

      case 'AlterColumnMetadata':
        return new AlterColumnMetadata($column);

      case 'AuditColumnMetadata':
        return new AuditColumnMetadata($column);

      default:
        throw new FallenException('type', $type);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Appends a table column to this list of table columns.
   *
   * @param ColumnMetadata $column The metadata of the table column.
   */
  public function appendTableColumn(ColumnMetadata $column): void
  {
    $this->columns[$column->getName()] = $column;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Appends table columns to this list of table columns.
   *
   * @param TableColumnsMetadata $columns The metadata of the table columns.
   */
  public function appendTableColumns(TableColumnsMetadata $columns): void
  {
    foreach ($columns->columns as $column)
    {
      $this->appendTableColumn($column);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Enhances all columns with field 'after'.
   */
  public function enhanceAfter(): void
  {
    $previous = null;
    foreach ($this->columns as $column)
    {
      $column->setAfter($previous);
      $previous = $column->getName();
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns a column given the column name.
   *
   * @param string $columnName The name of the column.
   *
   * @return ColumnMetadata
   */
  public function getColumn(string $columnName): ColumnMetadata
  {
    return $this->columns[$columnName];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the columns names.
   *
   * @return string[]
   */
  public function getColumnNames(): array
  {
    return array_keys($this->columns);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the underlying array with metadata of this list of table columns.
   *
   * @return ColumnMetadata[]
   */
  public function getColumns(): array
  {
    return $this->columns;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the length of the longest column name.
   *
   * @return int
   */
  public function getLongestColumnNameLength(): int
  {
    $max = 0;
    foreach ($this->columns as $column)
    {
      $max = max($max, mb_strlen($column->getName()));
    }

    return $max;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the number of columns.
   *
   * @return int
   */
  public function getNumberOfColumns(): int
  {
    return count($this->columns);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Makes all columns nullable.
   */
  public function makeNullable(): void
  {
    foreach ($this->columns as $column)
    {
      $column->makeNullable();
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Prepends table columns to this list of table columns.
   *
   * @param TableColumnsMetadata $columns The metadata of the table columns.
   */
  public function prependTableColumns(TableColumnsMetadata $columns): void
  {
    $this->columns = array_merge($columns->columns, $this->columns);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Removes a table column.
   *
   * @param string $columnName The table column name.
   */
  public function removeColumn(string $columnName): void
  {
    unset($this->columns[$columnName]);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Removes the default values from all columns.
   */
  public function unsetDefaults(): void
  {
    foreach ($this->columns as $column)
    {
      $column->unsetDefault();
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
