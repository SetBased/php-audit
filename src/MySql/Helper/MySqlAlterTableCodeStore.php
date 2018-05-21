<?php

namespace SetBased\Audit\MySql\Helper;

//----------------------------------------------------------------------------------------------------------------------
use SetBased\Helper\CodeStore\CodeStore;

/**
 * A helper class for automatically generating MySQL alter table syntax code with proper indentation.
 */
class MySqlAlterTableCodeStore extends CodeStore
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function indentationMode($line)
  {
    $mode = 0;

    $line = trim($line);

    if (substr($line, -6, 6)=='CHANGE')
    {
      $mode |= self::C_INDENT_INCREMENT_AFTER;
    }

    if (substr($line, 0, 1)==';')
    {
      $mode |= self::C_INDENT_DECREMENT_BEFORE;
    }

    return $mode;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
