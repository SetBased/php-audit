<?php
declare(strict_types=1);

namespace SetBased\Audit\Metadata;

/**
 * Class for the metadata of a database table.
 */
class TableMetadata
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The properties of the table that are stored by this class.
   *
   * var string[]
   */
  protected static array $fields = [];

  /**
   * The metadata of the columns of this table.
   *
   * @var TableColumnsMetadata
   */
  private TableColumnsMetadata $columns;

  /**
   * The the properties of this table column.
   *
   * @var array
   */
  private array $properties = [];

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param array[]              $properties The metadata of the table.
   * @param TableColumnsMetadata $columns    The metadata of the columns of this table.
   */
  public function __construct(array $properties, TableColumnsMetadata $columns)
  {
    foreach (static::$fields as $field)
    {
      if (isset($properties[$field]))
      {
        $this->properties[$field] = $properties[$field];
      }
    }

    $this->columns = $columns;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Compares two metadata of two tables. Returns an array with the names of the different properties.
   *
   * @param TableMetadata $table1 The metadata of the first table.
   * @param TableMetadata $table2 The metadata of the second table.
   *
   * @return string[]
   */
  public static function compareOptions(TableMetadata $table1, TableMetadata $table2): array
  {
    $diff = [];

    foreach (static::$fields as $field)
    {
      if (!in_array($field, ['table_schema', 'table_name']))
      {
        if ($table1->getProperty($field)!==$table2->getProperty($field))
        {
          $diff[] = $field;
        }
      }
    }

    return $diff;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns table columns.
   *
   * @return TableColumnsMetadata
   */
  public function getColumns(): TableColumnsMetadata
  {
    return $this->columns;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the options of this table.
   *
   * @return array[]
   */
  public function getOptions(): array
  {
    $ret = $this->properties;

    unset($ret['table_name']);
    unset($ret['table_schema']);

    return $ret;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns a property of this table.
   *
   * @param string $name The name of the property.
   *
   * @return string|null
   */
  public function getProperty(string $name): ?string
  {
    if (isset($this->properties[$name]))
    {
      return $this->properties[$name];
    }

    return null;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the name of schema.
   *
   * @return string
   */
  public function getSchemaName(): string
  {
    return $this->properties['table_schema'];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the name of this table.
   *
   * @return string
   */
  public function getTableName(): string
  {
    return $this->properties['table_name'];
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
