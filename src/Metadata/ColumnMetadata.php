<?php
declare(strict_types=1);

namespace SetBased\Audit\Metadata;

/**
 * Metadata of table columns.
 */
abstract class ColumnMetadata
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The properties of the column that are stored by this class.
   *
   * var string[]
   */
  protected static array $fields = ['column_name',
                                    'column_type',
                                    'is_nullable',
                                    'character_set_name',
                                    'collation_name'];

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
   * @param array $properties The metadata of the column.
   */
  public function __construct(array $properties)
  {
    foreach (static::$fields as $field)
    {
      if (isset($properties[$field]))
      {
        $this->properties[$field] = $properties[$field];
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Compares the metadata of two columns.
   *
   * @param ColumnMetadata $column1 The metadata of the first column.
   * @param ColumnMetadata $column2 The metadata of the second column.
   * @param string[]       $ignore  The properties to be ignored.
   *
   * @return bool True if the columns are equal, false otherwise.
   */
  public static function compare(ColumnMetadata $column1, ColumnMetadata $column2, array $ignore = []): bool
  {
    $equal = true;

    foreach (static::$fields as $field)
    {
      if (!in_array($field, $ignore))
      {
        if ($column1->getProperty($field)!==$column2->getProperty($field))
        {
          $equal = false;
        }
      }
    }

    return $equal;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns a SQL snippet with the column definition (without column name) of this column to be used in audit tables.
   *
   * @return string
   */
  abstract public function getColumnAuditDefinition(): string;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns a SQL snippet with the column definition (without column name) of this column.
   *
   * @return string
   */
  abstract public function getColumnDefinition(): string;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the name of this column.
   *
   * @return string
   */
  public function getName(): string
  {
    return $this->properties['column_name'];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the properties of this table column as an array.
   *
   * @return array
   */
  public function getProperties(): array
  {
    return $this->properties;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns a property of this table column.
   *
   * @param string $name The name of the property.
   *
   * @return string|null
   */
  public function getProperty(string $name): ?string
  {
    return $this->properties[$name] ?? null;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns column type info
   *
   * @return string
   */
  abstract public function getTypeInfo1(): string;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns additional column type info
   *
   * @return string|null
   */
  abstract public function getTypeInfo2(): ?string;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Make this column nullable.
   */
  public function makeNullable(): void
  {
    $this->properties['is_nullable'] = 'YES';
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Sets property 'after'.
   *
   * @param string|null $after
   */
  public function setAfter(?string $after): void
  {
    $this->properties['after'] = $after;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Removes the default value.
   */
  public function unsetDefault(): void
  {
    if (isset($this->properties['column_default']))
    {
      $this->properties['column_default'] = 'NULL';
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
