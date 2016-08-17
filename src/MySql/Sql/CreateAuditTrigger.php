<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\Audit\MySql\Sql;

use SetBased\Audit\MySql\Metadata\ColumnMetadata;
use SetBased\Audit\MySql\Metadata\TableColumnsMetadata;
use SetBased\Exception\FallenException;
use SetBased\Exception\RuntimeException;
use SetBased\Helper\CodeStore\MySqlCompoundSyntaxCodeStore;
use SetBased\Stratum\MySql\StaticDataLayer;

//----------------------------------------------------------------------------------------------------------------------
/**
 * Class for creating SQL statements for creating audit triggers.
 */
class CreateAuditTrigger
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Additional SQL statements.
   *
   * @var string[]
   */
  private $additionalSql;

  /**
   * AuditApplication columns from metadata.
   *
   * @var TableColumnsMetadata
   */
  private $auditColumns;

  /**
   * The name of the audit schema.
   *
   * @var string
   */
  private $auditSchemaName;

  /**
   * The generated code.
   *
   * @var MySqlCompoundSyntaxCodeStore
   */
  private $code;

  /**
   * The name of the data schema.
   *
   * @var string
   */
  private $dataSchemaName;

  /**
   * The skip variable.
   *
   * @var string
   */
  private $skipVariable;

  /**
   * Audit columns from metadata.
   *
   * @var TableColumnsMetadata
   */
  private $tableColumns;

  /**
   * The name of the data table.
   *
   * @var string
   */
  private $tableName;

  /**
   * The trigger action (i.e. INSERT, UPDATE, or DELETE).
   *
   * @var string
   */
  private $triggerAction;

  /**
   * The name of the trigger.
   *
   * @var string
   */
  private $triggerName;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Creates a trigger on a table.
   *
   * @param string               $dataSchemaName  The name of the data schema.
   * @param string               $auditSchemaName The name of the audit schema.
   * @param string               $tableName       The name of the table.
   * @param string               $triggerAction   The trigger action (i.e. INSERT, UPDATE, or DELETE).
   * @param string               $triggerName     The name of the trigger.
   * @param TableColumnsMetadata $auditColumns    The audit table columns.
   * @param TableColumnsMetadata $tableColumns    The data table columns.
   * @param string               $skipVariable    The skip variable.
   * @param string[]             $additionalSql   Additional SQL statements.
   */
  public function __construct($dataSchemaName,
                              $auditSchemaName,
                              $tableName,
                              $triggerName,
                              $triggerAction,
                              $auditColumns,
                              $tableColumns,
                              $skipVariable,
                              $additionalSql)
  {
    $this->dataSchemaName  = $dataSchemaName;
    $this->auditSchemaName = $auditSchemaName;
    $this->tableName       = $tableName;
    $this->triggerName     = $triggerName;
    $this->triggerAction   = $triggerAction;
    $this->skipVariable    = $skipVariable;
    $this->auditColumns    = $auditColumns;
    $this->tableColumns    = $tableColumns;
    $this->additionalSql   = $additionalSql;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the SQL code for creating an audit trigger.
   *
   * @throws FallenException
   */
  public function buildStatement()
  {
    $this->code = new MySqlCompoundSyntaxCodeStore();

    $rowState = [];
    switch ($this->triggerAction)
    {
      case 'INSERT':
        $rowState[] = 'NEW';
        break;

      case 'DELETE':
        $rowState[] = 'OLD';
        break;

      case 'UPDATE':
        $rowState[] = 'OLD';
        $rowState[] = 'NEW';
        break;

      default:
        throw new FallenException('action', $this->triggerAction);
    }

    $this->code->append(sprintf('create trigger `%s`.`%s`', $this->dataSchemaName, $this->triggerName));
    $this->code->append(sprintf('after %s on `%s`.`%s`', strtolower($this->triggerAction), $this->dataSchemaName, $this->tableName));
    $this->code->append('for each row');
    $this->code->append('begin');

    if ($this->skipVariable!==null) $this->code->append(sprintf('if (%s is null) then', $this->skipVariable));

    $this->code->append($this->additionalSql);

    $this->createInsertStatement($rowState[0]);
    if (sizeof($rowState)==2)
    {
      $this->createInsertStatement($rowState[1]);
    }

    if ($this->skipVariable!==null) $this->code->append('end if;');
    $this->code->append('end');

    return $this->code->getCode();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Adds an insert SQL statement to SQL code for a trigger.
   *
   * @param string $rowState The row state (i.e. OLD or NEW).
   */
  private function createInsertStatement($rowState)
  {
    $this->createInsertStatementInto();
    $this->createInsertStatementValues($rowState);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Adds the "insert into" part of an insert SQL statement to SQL code for a trigger.
   */
  private function createInsertStatementInto()
  {
    $columnNames = '';

    // First the audit columns.
    /** @var ColumnMetadata $column */
    foreach ($this->auditColumns->getColumns() as $column)
    {
      if ($columnNames) $columnNames .= ',';
      $columnNames .= sprintf('`%s`', $column->getProperty('column_name'));
    }

    // Second the audit columns.
    /** @var ColumnMetadata $column */
    foreach ($this->tableColumns->getColumns() as $column)
    {
      if ($columnNames) $columnNames .= ',';
      $columnNames .= sprintf('`%s`', $column->getProperty('column_name'));
    }

    $this->code->append(sprintf('insert into `%s`.`%s`(%s)', $this->auditSchemaName, $this->tableName, $columnNames));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Adds the "values" part of an insert SQL statement to SQL code for a trigger.
   *
   * @param string $rowState The row state (i.e. OLD or NEW).
   */
  private function createInsertStatementValues($rowState)
  {
    $values = '';

    // First the values for the audit columns.
    /** @var ColumnMetadata $column */
    foreach ($this->auditColumns->getColumns() as $column)
    {
      $column = $column->getProperties();
      if ($values) $values .= ',';
      switch (true)
      {
        case (isset($column['value_type'])):
          switch ($column['value_type'])
          {
            case 'ACTION':
              $values .= StaticDataLayer::quoteString($this->triggerAction);
              break;

            case 'STATE':
              $values .= StaticDataLayer::quoteString($rowState);
              break;

            default:
              throw new FallenException('audit_value_type', ($column['value_type']));
          }
          break;

        case (isset($column['expression'])):
          $values .= $column['expression'];
          break;

        default:
          throw new RuntimeException('None of audit_value_type and audit_expression are set.');
      }
    }

    // Second the values for the audit columns.
    /** @var ColumnMetadata $column */
    foreach ($this->tableColumns->getColumns() as $column)
    {
      if ($values) $values .= ',';
      $values .= sprintf('%s.`%s`', $rowState, $column->getProperty('column_name'));
    }

    $this->code->append(sprintf('values(%s);', $values));
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
