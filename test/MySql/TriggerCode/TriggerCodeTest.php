<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\Audit\Test\MySql\TriggerCode;

use PHPUnit_Framework_TestCase;
use SetBased\Audit\MySql\Metadata\TableColumnsMetadata;
use SetBased\Audit\MySql\Sql\CreateAuditTrigger;

//----------------------------------------------------------------------------------------------------------------------
/**
 * Tests on trigger code.
 */
class TriggerCodeTest extends PHPUnit_Framework_TestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  public function test01()
  {
    $actions = ['INSERT', 'UPDATE', 'DELETE'];
    
    foreach ($actions as $action)
    {
      $this->triggerEndingTest($action);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  public function test10()
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
  private function additionalSqlTest($triggerAction, $additionalSql)
  {
    $audit_columns = new TableColumnsMetadata([]);

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
        $this->assertContains($line.PHP_EOL, $sql, sprintf('%s: %s', $line, $triggerAction));
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  private function triggerEndingTest($triggerAction)
  {
    $audit_columns = new TableColumnsMetadata([]);

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
    $this->assertRegExp('/\r?\n$/', $sql, sprintf('Single EOL: %s', $triggerAction));

    // Code must have one and only EOL at the end.
    $this->assertNotRegExp('/\r?\n\r?\n$/', $sql, sprintf('Double EOL: %s', $triggerAction));

    // Code must not have a semicolon at the end.
    $this->assertNotRegExp('/;$/', trim($sql), sprintf('Semicolon: %s', $triggerAction));
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
