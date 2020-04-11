<?php
declare(strict_types=1);

namespace SetBased\Audit\Test\MySql\DiffCommand\NewColumnDataTable;

use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Audit\Test\MySql\DiffCommand\DiffCommandTestCase;

/**
 * Tests new column in data table.
 */
class NewColumnDataTableTest extends DiffCommandTestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  public static function setUpBeforeClass(): void
  {
    self::$dir = __DIR__;

    parent::setUpBeforeClass();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Runs the test.
   */
  public function test01(): void
  {
    $this->runAudit();

    // Create new column.
    AuditDataLayer::$dl->executeMulti(file_get_contents(__DIR__.'/config/create_new_column.sql'));

    $output = preg_replace('/ +/', ' ', $this->runDiff());

    self::assertStringContainsString('| c3 | | mediumint(9) |', $output, 'acquire');
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
