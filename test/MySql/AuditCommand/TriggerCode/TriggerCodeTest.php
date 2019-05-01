<?php
declare(strict_types=1);

namespace SetBased\Audit\Test\MySql\AuditCommand\TriggerCode;

use PHPUnit\Framework\TestCase;
use SetBased\Audit\Metadata\TableColumnsMetadata;
use SetBased\Audit\MySql\Sql\CreateAuditTrigger;

/**
 * Tests on trigger code.
 */
class TriggerCodeTest extends TestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  public function test01(): void
  {
    $actions = ['INSERT', 'UPDATE', 'DELETE'];

    foreach ($actions as $action)
    {
      $this->triggerEndingTest($action);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  public function test10(): void
  {
    $lines2 = [null,
               [],
               ['// Line 1',
                '// Line 2']];

    $actions = ['INSERT', 'UPDATE', 'DELETE'];

    foreach ($lines2 as $lines)
    {
      foreach ($actions as $action)
      {
        $this->additionalSqlTest($action, $lines);
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test case for additional trigger code.
   *
   * @param string        $triggerAction The trigger action (i.e. INSERT, UPDATE, or DELETE).
   * @param string[]|null $additionalSql Additional SQL statements
   */
  private function additionalSqlTest(string $triggerAction, ?array $additionalSql): void
  {
    $audit_columns = new TableColumnsMetadata();

    $table_columns = new TableColumnsMetadata([['column_name'        => 'x',
                                                'column_type'        => 'int(11)',
                                                'is_nullable'        => 'YES',
                                                'character_set_name' => null,
                                                'collation_name'     => null]]);

    $helper = new CreateAuditTrigger('test_data',
                                     'test_audit',
                                     'MY_TABLE',
                                     'my_trigger',
                                     $triggerAction,
                                     $audit_columns,
                                     $table_columns,
                                     null,
                                     $additionalSql);

    $sql = $helper->buildStatement();

    if (is_array($additionalSql))
    {
      foreach ($additionalSql as $line)
      {
        self::assertStringContainsString($line.PHP_EOL, $sql, sprintf('%s: %s', $line, $triggerAction));
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test cases for end of SQL code of trigger.
   *
   * @param string $triggerAction The trigger action (i.e. INSERT, UPDATE, or DELETE).
   */
  private function triggerEndingTest(string $triggerAction): void
  {
    $audit_columns = new TableColumnsMetadata();

    $table_columns = new TableColumnsMetadata([['column_name'        => 'x',
                                                'column_type'        => 'int(11)',
                                                'is_nullable'        => 'YES',
                                                'character_set_name' => null,
                                                'collation_name'     => null]]);

    $helper = new CreateAuditTrigger('test_data',
                                     'test_audit',
                                     'MY_TABLE',
                                     'my_trigger',
                                     $triggerAction,
                                     $audit_columns,
                                     $table_columns,
                                     null,
                                     []);

    $sql = $helper->buildStatement();

    // Code must have one EOL at the end.
    self::assertRegExp('/\r?\n$/', $sql, sprintf('Single EOL: %s', $triggerAction));

    // Code must have one and only EOL at the end.
    self::assertNotRegExp('/\r?\n\r?\n$/', $sql, sprintf('Double EOL: %s', $triggerAction));

    // Code must not have a semicolon at the end.
    self::assertNotRegExp('/;$/', trim($sql), sprintf('Semicolon: %s', $triggerAction));
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
