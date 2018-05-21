<?php

namespace SetBased\Audit\Test\MySql\AuditCommand\LockTable;

use SetBased\Stratum\MySql\StaticDataLayer;

/**
 * Tests for table locking.
 */
class LockTableMemoryTest extends LockTableTestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   *
   */
  public static function setUpBeforeClass()
  {
    parent::setUpBeforeClass();

    StaticDataLayer::disconnect();
    StaticDataLayer::connect('localhost', 'test', 'test', self::$dataSchema);

    StaticDataLayer::executeNone('alter table `test_data`.`TABLE1` engine=memory');
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
