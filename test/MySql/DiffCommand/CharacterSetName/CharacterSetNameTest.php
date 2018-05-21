<?php

namespace SetBased\Audit\Test\MySql\DiffCommand\CharacterSetName;

use SetBased\Audit\Test\MySql\DiffCommand\DiffCommandTestCase;
use SetBased\Stratum\MySql\StaticDataLayer;

/**
 * Tests changed character set of a column.
 */
class CharacterSetNameTest extends DiffCommandTestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public static function setUpBeforeClass()
  {
    self::$dir = __DIR__;

    parent::setUpBeforeClass();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Runs the test.
   */
  public function test01()
  {
    $this->runAudit();

    // Change character set of column c4.
    StaticDataLayer::multiQuery(file_get_contents(__DIR__.'/config/change_charset.sql'));

    $output = $this->runDiff();

    self::assertContains('| c4     | varchar(20)                | varchar(20)              | int(11) |', $output);
    self::assertContains('|        | [ascii] [ascii_general_ci] | [utf8] [utf8_general_ci] |         |', $output);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
