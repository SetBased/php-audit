<?php
declare(strict_types=1);

namespace SetBased\Audit\Test\MySql\AuditCommand\LockTable;

use SetBased\Audit\MySql\AuditDataLayer;

/**
 * Tests for table locking.
 */
class LockTableMyisamTest extends LockTableTestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   *
   */
  public static function setUpBeforeClass(): void
  {
    parent::setUpBeforeClass();

    AuditDataLayer::$dl->disconnect();
    AuditDataLayer::$dl->connect();

    AuditDataLayer::$dl->executeNone('alter table `test_data`.`TABLE1` engine=myisam');
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
