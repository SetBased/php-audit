<?php

namespace SetBased\Audit\Test\MySql\DiffCommand\NewColumnAuditTable;

use SetBased\Audit\Test\MySql\DiffCommand\DiffCommandTestCase;
use SetBased\Stratum\MySql\StaticDataLayer;

/**
 * Tests new column in audit table.
 */
class NewColumnAuditTableTest extends DiffCommandTestCase
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
    // Run audit.
    $this->runAudit();

    // Create new column c3 in the audit table.
    StaticDataLayer::executeMulti(file_get_contents(__DIR__.'/config/create_new_column.sql'));

    $output = preg_replace('/\ +/', ' ', $this->runDiff());

    self::assertContains('| c3 | mediumint(9) | |', $output, 'acquire');
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
