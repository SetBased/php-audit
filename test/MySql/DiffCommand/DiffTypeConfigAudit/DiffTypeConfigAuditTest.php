<?php

namespace SetBased\Audit\Test\MySql\DiffCommand\DiffTypeConfigAudit;

use SetBased\Audit\Test\MySql\DiffCommand\DiffCommandTestCase;
use SetBased\Stratum\MySql\StaticDataLayer;

/**
 * Tests changed column type.
 */
class DiffTypeConfigAuditTest extends DiffCommandTestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
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

    // Change type of column c4.
    StaticDataLayer::multiQuery(file_get_contents(__DIR__.'/config/change_column_type.sql'));

    $output = preg_replace('/\ +/', ' ', $this->runDiff());

    self::assertContains('| c4 | int(11) | varchar(20) |', $output);
    self::assertContains('| | | [utf8] [utf8_general_ci] |', $output);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
