<?php
declare(strict_types=1);

namespace SetBased\Audit\MySql\Metadata;

use SetBased\Audit\Metadata\TableMetadata as BaseTableMetadata;

/**
 * Class for metadata of a MySQL table.
 */
class TableMetadata extends BaseTableMetadata
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The properties of the table that are stored by this class.
   *
   * var string[]
   */
  protected static array $fields = ['table_schema',
                                    'table_name',
                                    'engine',
                                    'character_set_name',
                                    'table_collation'];

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
