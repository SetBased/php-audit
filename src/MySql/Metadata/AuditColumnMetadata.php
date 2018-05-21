<?php

namespace SetBased\Audit\MySql\Metadata;

/**
 * Metadata of an audit table column in an audit table.
 */
class AuditColumnMetadata extends ColumnMetadata
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
                              'collation_name',
                              'expression',
                              'value_type'];

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
