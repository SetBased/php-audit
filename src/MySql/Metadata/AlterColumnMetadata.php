<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\Audit\MySql\Metadata;

//----------------------------------------------------------------------------------------------------------------------
/**
 * Class for the metadata of a audit column or TableColumnsMetadata with audit and data columns.
 */
class AlterColumnMetadata extends ColumnMetadata
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param array[]|ColumnMetadata $properties  The metadata of the column.
   * @param array[]|ColumnMetadata $afterColumn After column name.
   */
  public function __construct($properties, $afterColumn)
  {
    self::$fields[] = 'after';

    parent::__construct($properties);
  }
}

//----------------------------------------------------------------------------------------------------------------------
