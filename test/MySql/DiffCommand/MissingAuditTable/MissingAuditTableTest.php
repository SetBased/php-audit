<?php
declare(strict_types=1);

namespace SetBased\Audit\Test\MySql\DiffCommand\CharacterSetName;

use SetBased\Audit\Test\MySql\DiffCommand\DiffCommandTestCase;
use SetBased\Stratum\MySql\StaticDataLayer;

/**
 * Tests missing audit table.
 */
class MissingAuditTableTest extends DiffCommandTestCase
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

    StaticDataLayer::executeMulti(file_get_contents(__DIR__.'/config/drop_audit_table.sql'));

    $output = $this->runDiff();

    $expected = <<< EOT
Missing Audit Tables
====================

 * TABLE2
EOT;

    self::assertSame(trim($expected), trim($output));
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
