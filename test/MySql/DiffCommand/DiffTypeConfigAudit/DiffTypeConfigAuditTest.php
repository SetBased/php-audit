<?php
declare(strict_types=1);

namespace SetBased\Audit\Test\MySql\DiffCommand\DiffTypeConfigAudit;

use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Audit\Test\MySql\DiffCommand\DiffCommandTestCase;

/**
 * Tests changed column type.
 */
class DiffTypeConfigAuditTest extends DiffCommandTestCase
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

    // Change type of column c4.
    AuditDataLayer::$dl->executeMulti(file_get_contents(__DIR__.'/config/change_column_type.sql'));

    $output = preg_replace('/ +/', ' ', $this->runDiff());

    // Fix for MariaDB 10.6+.
    $output = str_replace('utf8mb3', 'utf8', $output);

    // Fix for MySQL 8.x.
    $output = str_replace('int ', 'int(11) ', $output);

    self::assertStringContainsString('| c4 | int(11) | varchar(20) |', $output);
    self::assertStringContainsString('| | | [utf8] [utf8_general_ci] |', $output);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
