<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\Audit\MySql\Metadata;

//----------------------------------------------------------------------------------------------------------------------
/**
 * Class for the metadata of a column.
 */
class ColumnMetadata
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The properties of table columns that are stored by this class.
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
   * @var array<string,string>
   */
  protected $properties = [];

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param array[]|ColumnMetadata $properties The metadata of the column.
   */
  public function __construct($properties)
  {
    if (!is_array($properties))
    {
      $properties = $properties->getProperties();
    }
    foreach (self::$fields as $field)
    {
      if (isset($properties[$field]))
      {
        $this->properties[$field] = $properties[$field];
      }
    }

    // XXX Must be in some other place, i guess.
    // See to move in logic for create table.
    if ($this->properties['column_type']==='timestamp')
    {
      $this->properties['column_type'] = $this->properties['column_type'].' NULL';
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the properties of this table column as an array.
   *
   * @return array[]
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
}

//----------------------------------------------------------------------------------------------------------------------
