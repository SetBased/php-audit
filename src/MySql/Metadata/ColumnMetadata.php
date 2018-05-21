<?php

namespace SetBased\Audit\MySql\Metadata;

/**
 * Metadata of table columns.
 */
class ColumnMetadata
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The properties of the column that are stored by this class.
   *
   * var string[]
   */
  protected static $fields = ['column_name',
                              'column_type',
                              'is_nullable',
                              'character_set_name',
                              'collation_name'];

  /**
   * The the properties of this table column.
   *
   * @var array
   */
  protected $properties = [];

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param array $properties The metadata of the column.
   */
  public function __construct($properties)
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
   * Compares two the metadata of the columns.
   *
   * @param ColumnMetadata $column1 The metadata of the first column.
   * @param ColumnMetadata $column2 The metadata of the second column.
   * @param string[]       $ignore  The properties to be ignored.
   *
   * @return bool True if the columns are equal, false otherwise.
   */
  public static function compare($column1, $column2, $ignore = [])
  {
    $equal = true;

    foreach (self::$fields as $field)
    {
      if (!in_array($field, $ignore))
      {
        if ($column1->getProperty($field)!=$column2->getProperty($field))
        {
          $equal = false;
        }
      }
    }

    return $equal;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the properties of this table column as an array.
   *
   * @return array
   */
  public function getProperties()
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
  public function getProperty($name)
  {
    if (isset($this->properties[$name]))
    {
      return $this->properties[$name];
    }

    return null;
  }
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Make this column nullable.
   */
  public function makeNullable()
  {
    $this->properties['is_nullable'] = 'YES';
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
