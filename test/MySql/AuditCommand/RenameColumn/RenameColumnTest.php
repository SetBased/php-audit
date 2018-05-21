<?php

namespace SetBased\Audit\Test\MySql\AuditCommand\RenameColumn;

use SetBased\Audit\Test\MySql\AuditCommand\AuditCommandTestCase;
use SetBased\Stratum\MySql\Exception\DataLayerException;
use SetBased\Stratum\MySql\StaticDataLayer;

/**
 * Tests for running audit with a renamed column.
 */
class RenameColumnTest extends AuditCommandTestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public static function setUpBeforeClass()
  {
    self::$dir = __DIR__;

    parent::setUpBeforeClass();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Run audit on a table with a renamed column.
   */
  public function test01()
  {
    // Run audit.
    $this->runAudit();

    // Insert a row into TABLE1.
    StaticDataLayer::query('insert into `TABLE1`(c1, c2, c3, c4) values(1, 2, 3, 4)');

    // Rename column c3 and d3.
    StaticDataLayer::multiQuery(file_get_contents(__DIR__.'/config/rename_column.sql'));

    // We expect exit status 1.
    $this->runAudit(1);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Trigger must use column c3 that does not exists anymore.
   */
  public function test02()
  {
    try
    {
      StaticDataLayer::query('insert into `TABLE1`(c1, c2, c4) values(1, 2, 4)');
    }
    catch (DataLayerException $e)
    {
      self::assertContains('Unknown column', $e->getMessage());
      self::assertContains('c3', $e->getMessage());
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Trigger must use column c3 that does not exists anymore.
   */
  public function test03()
  {
    try
    {
      StaticDataLayer::query('update `TABLE1` set c1=10, c2=20, c4=40');
    }
    catch (DataLayerException $e)
    {
      self::assertContains('Unknown column', $e->getMessage());
      self::assertContains('c3', $e->getMessage());
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Trigger must use column c3 that does not exists anymore.
   */
  public function test04()
  {
    try
    {
      StaticDataLayer::query('delete from `TABLE1`');
    }
    catch (DataLayerException $e)
    {
      self::assertContains('Unknown column', $e->getMessage());
      self::assertContains('c3', $e->getMessage());
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
