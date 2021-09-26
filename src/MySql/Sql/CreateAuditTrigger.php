<?php
declare(strict_types=1);

namespace SetBased\Audit\MySql\Sql;

use SetBased\Audit\Metadata\TableColumnsMetadata;
use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Exception\FallenException;
use SetBased\Exception\RuntimeException;
use SetBased\Helper\CodeStore\MySqlCompoundSyntaxCodeStore;

/**
 * Class for creating SQL statements for creating audit triggers.
 */
class CreateAuditTrigger
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The metadata of the additional audit columns.
   *
   * @var TableColumnsMetadata
   */
  private TableColumnsMetadata $additionalAuditColumns;

  /**
   * Additional SQL statements.
   *
   * @var string[]|null
   */
  private ?array $additionalSql;

  /**
   * The name of the audit schema.
   *
   * @var string
   */
  private string $auditSchemaName;

  /**
   * The generated code.
   *
   * @var MySqlCompoundSyntaxCodeStore
   */
  private MySqlCompoundSyntaxCodeStore $code;

  /**
   * The name of the data schema.
   *
   * @var string
   */
  private string $dataSchemaName;

  /**
   * The skip variable.
   *
   * @var string|null
   */
  private ?string $skipVariable;

  /**
   * Audit columns from metadata.
   *
   * @var TableColumnsMetadata
   */
  private TableColumnsMetadata $tableColumns;

  /**
   * The name of the data table.
   *
   * @var string
   */
  private string $tableName;

  /**
   * The trigger action (i.e. INSERT, UPDATE, or DELETE).
   *
   * @var string
   */
  private string $triggerAction;

  /**
   * The name of the trigger.
   *
   * @var string
   */
  private string $triggerName;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Creates a trigger on a table.
   *
   * @param string               $dataSchemaName         The name of the data schema.
   * @param string               $auditSchemaName        The name of the audit schema.
   * @param string               $tableName              The name of the table.
   * @param string               $triggerAction          The trigger action (i.e. INSERT, UPDATE, or DELETE).
   * @param string               $triggerName            The name of the trigger.
   * @param TableColumnsMetadata $additionalAuditColumns The metadata of the additional audit columns.
   * @param TableColumnsMetadata $tableColumns           The metadata of the data table columns.
   * @param string|null          $skipVariable           The skip variable.
   * @param string[]|null        $additionalSql          Additional SQL statements.
   */
  public function __construct(string $dataSchemaName,
                              string $auditSchemaName,
                              string $tableName,
                              string $triggerName,
                              string $triggerAction,
                              TableColumnsMetadata $additionalAuditColumns,
                              TableColumnsMetadata $tableColumns,
                              ?string $skipVariable,
                              ?array $additionalSql)
  {
    $this->dataSchemaName         = $dataSchemaName;
    $this->auditSchemaName        = $auditSchemaName;
    $this->tableName              = $tableName;
    $this->triggerName            = $triggerName;
    $this->triggerAction          = $triggerAction;
    $this->skipVariable           = $skipVariable;
    $this->additionalAuditColumns = $additionalAuditColumns;
    $this->tableColumns           = $tableColumns;
    $this->additionalSql          = $additionalSql;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the SQL code for creating an audit trigger.
   *
   * @return string
   *
   * @throws FallenException
   */
  public function buildStatement(): string
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
    $this->code->append(sprintf('after %s on `%s`.`%s`',
                                strtolower($this->triggerAction),
                                $this->dataSchemaName,
                                $this->tableName));
    $this->code->append('for each row');
    $this->code->append('begin');

    if ($this->skipVariable!==null)
    {
      $this->code->append(sprintf('if (%s is null) then', $this->skipVariable));
    }

    $this->code->append($this->additionalSql);

    $this->createInsertStatement($rowState[0]);
    if (count($rowState)===2)
    {
      $this->createInsertStatement($rowState[1]);
    }

    if ($this->skipVariable!==null)
    {
      $this->code->append('end if;');
    }
    $this->code->append('end');

    return $this->code->getCode();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Adds an insert SQL statement to SQL code for a trigger.
   *
   * @param string $rowState The row state (i.e. OLD or NEW).
   */
  private function createInsertStatement(string $rowState): void
  {
    $this->createInsertStatementInto();
    $this->createInsertStatementValues($rowState);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Adds the "insert into" part of an insert SQL statement to SQL code for a trigger.
   */
  private function createInsertStatementInto(): void
  {
    $columnNames = '';

    // First the audit columns.
    foreach ($this->additionalAuditColumns->getColumns() as $column)
    {
      if ($columnNames!=='')
      {
        $columnNames .= ',';
      }
      $columnNames .= sprintf('`%s`', $column->getName());
    }

    // Second the audit columns.
    foreach ($this->tableColumns->getColumns() as $column)
    {
      if ($columnNames!=='')
      {
        $columnNames .= ',';
      }
      $columnNames .= sprintf('`%s`', $column->getName());
    }

    $this->code->append(sprintf('insert into `%s`.`%s`(%s)', $this->auditSchemaName, $this->tableName, $columnNames));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Adds the "values" part of an insert SQL statement to SQL code for a trigger.
   *
   * @param string $rowState The row state (i.e. OLD or NEW).
   */
  private function createInsertStatementValues(string $rowState): void
  {
    $values = '';

    // First the values for the audit columns.
    foreach ($this->additionalAuditColumns->getColumns() as $column)
    {
      $column = $column->getProperties();
      if ($values!=='')
      {
        $values .= ',';
      }

      switch (true)
      {
        case (isset($column['value_type'])):
          switch ($column['value_type'])
          {
            case 'ACTION':
              $values .= AuditDataLayer::$dl->quoteString($this->triggerAction);
              break;

            case 'STATE':
              $values .= AuditDataLayer::$dl->quoteString($rowState);
              break;

            default:
              throw new FallenException('value_type', ($column['value_type']));
          }
          break;

        case (isset($column['expression'])):
          $values .= $column['expression'];
          break;

        default:
          throw new RuntimeException('None of value_type and expression are set.');
      }
    }

    // Second the values for the audit columns.
    foreach ($this->tableColumns->getColumns() as $column)
    {
      if ($values!=='')
      {
        $values .= ',';
      }
      $values .= sprintf('%s.`%s`', $rowState, $column->getName());
    }

    $this->code->append(sprintf('values(%s);', $values));
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
