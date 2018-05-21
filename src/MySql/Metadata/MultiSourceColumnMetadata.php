<?php

namespace SetBased\Audit\MySql\Metadata;

/**
 * Metadata of an audit table column in an audit table.
 */
class MultiSourceColumnMetadata extends ColumnMetadata
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The properties of table columns that are stored by this class.
   *
   * var string[]
   */
  protected static $fields = ['data',
                              'audit',
                              'config'];

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
