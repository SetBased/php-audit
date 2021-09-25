<?php
declare(strict_types=1);

namespace SetBased\Audit\MySql\Metadata;

/**
 * Metadata of a table column for altering a column in an audit table.
 */
class AlterColumnMetadata extends ColumnMetadata
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The properties of table columns that are stored by this class.
   *
   * var string[]
   */
  protected static array $fields = ['column_name',
                                    'column_type',
                                    'is_nullable',
                                    'character_set_name',
                                    'collation_name',
                                    'after'];

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
