<?php
declare(strict_types=1);

namespace SetBased\Audit\MySql;

use SetBased\Helper\CodeStore\CodeStore;

/**
 * A helper class for automatically generating MySQL alter table syntax code with proper indentation.
 */
class AlterTableCodeStore extends CodeStore
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function indentationMode(string $line): int
  {
    $mode = 0;

    $line = trim($line);

    if (substr($line, 0, 11)==='alter table' && substr($line, -1)<>';')
    {
      $mode |= self::C_INDENT_INCREMENT_AFTER;
    }

    if (substr($line, 0, 1)===';')
    {
      $mode |= self::C_INDENT_DECREMENT_BEFORE;
    }

    return $mode;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
