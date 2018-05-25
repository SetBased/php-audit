<?php

namespace SetBased\Audit\Test\MySql\DiffCommand\NewColumnDataTable;

use SetBased\Audit\Test\MySql\DiffCommand\DiffCommandTestCase;
use SetBased\Stratum\MySql\StaticDataLayer;

/**
 * Tests new column in data table.
 */
class NewColumnDataTableTest extends DiffCommandTestCase
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

    // Create new column.
    StaticDataLayer::multiQuery(file_get_contents(__DIR__.'/config/create_new_column.sql'));

    $output = preg_replace('/\ +/', ' ', $this->runDiff());

    self::assertContains('| c3 | | mediumint(9) |', $output, 'acquire');
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
