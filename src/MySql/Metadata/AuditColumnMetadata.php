<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\Audit\MySql\Metadata;

//----------------------------------------------------------------------------------------------------------------------
/**
 * Class for the metadata of a audit column or TableColumnsMetadata with audit and data columns.
 */
class AuditColumnMetadata extends ColumnMetadata
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param array[]|ColumnMetadata $properties The metadata of the column.
   */
  public function __construct($properties)
  {
    self::$fields[] = 'expression';
    self::$fields[] = 'value_type';

    parent::__construct($properties);
  }
}

//----------------------------------------------------------------------------------------------------------------------
