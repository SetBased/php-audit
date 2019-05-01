<?php
declare(strict_types=1);

namespace SetBased\Audit\Test\MySql\AuditCommand\LockTable;

use SetBased\Stratum\MySql\StaticDataLayer;

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

    StaticDataLayer::disconnect();
    StaticDataLayer::connect('localhost', 'test', 'test', self::$dataSchema);

    StaticDataLayer::executeNone('alter table `test_data`.`TABLE1` engine=myisam');
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
