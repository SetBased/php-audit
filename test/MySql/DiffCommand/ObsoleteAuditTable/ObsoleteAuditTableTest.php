<?php
declare(strict_types=1);

namespace SetBased\Audit\Test\MySql\DiffCommand\ObsoleteAuditTable;

use SetBased\Audit\Test\MySql\DiffCommand\DiffCommandTestCase;
use SetBased\Stratum\MySql\StaticDataLayer;

/**
 * Tests missing audit table.
 */
class ObsoleteAuditTableTest extends DiffCommandTestCase
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
    copy(__DIR__.'/config/audit.org.json', __DIR__.'/config/audit.json');

    $this->runAudit();

    StaticDataLayer::executeMulti(file_get_contents(__DIR__.'/config/drop_data_table.sql'));

    $this->runAudit(0, true);

    // Run diff command without --full option.
    $output = $this->runDiff();
    self::assertSame('', trim($output));

    // Run diff command with --full option.
    $output   = $this->runDiff(true);
    $expected = <<< EOT
Obsolete Audit Tables
=====================

 * TABLE2
EOT;
    self::assertStringContainsString(trim($expected), $output);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
