<?php
declare(strict_types=1);

namespace SetBased\Audit\MySql\Metadata;

use SetBased\Audit\Metadata\ColumnMetadata as BaseColumnMetadata;

/**
 * Metadata of table columns.
 */
class ColumnMetadata extends BaseColumnMetadata
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

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  public function getColumnAuditDefinition(): string
  {
    $parts = [];

    if ($this->getProperty('column_type')!==null)
    {
      $parts[] = $this->getProperty('column_type');
    }

    if ($this->getProperty('character_set_name')!==null)
    {
      $parts[] = 'character set '.$this->getProperty('character_set_name');
    }

    if ($this->getProperty('collation_name')!==null)
    {
      $parts[] = 'collate '.$this->getProperty('collation_name');
    }

    $parts[] = ($this->getProperty('is_nullable')==='YES') ? 'null' : 'not null';

    if ($this->getProperty('column_default')!==null && $this->getProperty('column_default')!=='NULL')
    {
      $parts[] = 'default '.$this->getProperty('column_default');
    }
    elseif ($this->getProperty('column_type')==='timestamp')
    {
      // Prevent automatic updates of timestamp columns.
      $parts[] = 'default null';
    }

    return implode(' ', $parts);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  public function getColumnDefinition(): string
  {
    $parts = [];

    if ($this->getProperty('column_type')!==null)
    {
      $parts[] = $this->getProperty('column_type');
    }

    if ($this->getProperty('character_set_name')!==null)
    {
      $parts[] = 'character set '.$this->getProperty('character_set_name');
    }

    if ($this->getProperty('collation_name')!==null)
    {
      $parts[] = 'collate '.$this->getProperty('collation_name');
    }

    $parts[] = ($this->getProperty('is_nullable')==='YES') ? 'null' : 'not null';

    if ($this->getProperty('column_default')!==null && $this->getProperty('column_default')!=='NULL')
    {
      $parts[] = 'default '.$this->getProperty('column_default');
    }

    return implode(' ', $parts);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  public function getTypeInfo1(): string
  {
    if ($this->getProperty('is_nullable')==='YES')
    {
      return $this->getProperty('column_type');
    }

    return $this->getProperty('column_type').' not null';
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  public function getTypeInfo2(): ?string
  {
    if ($this->getProperty('collation_name')!==null)
    {
      return sprintf('[%s] [%s]', $this->getProperty('character_set_name'), $this->getProperty('collation_name'));
    }

    return null;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
