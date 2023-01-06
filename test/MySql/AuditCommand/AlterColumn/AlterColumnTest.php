<?php
declare(strict_types=1);

namespace SetBased\Audit\Test\MySql\AuditCommand\AlterColumn;

use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Audit\Test\MySql\AuditCommand\AuditCommandTestCase;

/**
 * Tests for running audit with an altered table column.
 */
class AlterColumnTest extends AuditCommandTestCase
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
  public function test01(): void
  {
    $this->runAudit();

    AuditDataLayer::$dl->executeMulti(file_get_contents(__DIR__.'/config/alter_column.sql'));

    $output = $this->runAudit();
    self::assertSame('Type of TABLE1.c2 has been altered to varchar(80)', trim($output));
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
