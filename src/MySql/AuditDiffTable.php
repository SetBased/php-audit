<?php

namespace SetBased\Audit\MySql;

use SetBased\Audit\MySql\Helper\DiffTableColumns;
use SetBased\Audit\MySql\Metadata\MultiSourceColumnMetadata;
use SetBased\Audit\MySql\Metadata\TableColumnsMetadata;

/**
 * Class for executing auditing actions for tables.
 */
class AuditDiffTable
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Audit database schema.
   *
   * @var string
   */
  private $auditSchema;

  /**
   * Audit columns from config file.
   *
   * @var array[]
   */
  private $configAuditColumns;

  /**
   * Audit columns from config file
   *
   * @var array[]
   */
  private $configColumns;

  /**
   * Data database schema.
   *
   * @var string
   */
  private $dataSchema;

  /**
   * Difference between data and audit tables.
   *
   * @var DiffTableColumns
   */
  private $diffColumns;

  /**
   * Table name.
   *
   * @var string
   */
  private $tableName;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param string   $dataSchema         Data database schema.
   * @param string   $auditSchema        Audit database schema.
   * @param string   $tableName          Table name.
   * @param array[] $configAuditColumns Audit columns from config file.
   * @param array[] $configColumns      Data columns from config file.
   */
  public function __construct($dataSchema, $auditSchema, $tableName, $configAuditColumns, $configColumns)
  {
    $this->dataSchema         = $dataSchema;
    $this->auditSchema        = $auditSchema;
    $this->tableName          = $tableName;
    $this->configAuditColumns = $configAuditColumns;
    $this->configColumns      = array_merge($configAuditColumns, $configColumns);

    $dataColumns  = new TableColumnsMetadata(AuditDataLayer::getTableColumns($this->dataSchema, $this->tableName));
    $auditColumns = AuditDataLayer::getTableColumns($this->auditSchema, $this->tableName);
    $auditColumns = $this->addNotNull($auditColumns);
    $auditColumns = new TableColumnsMetadata($auditColumns);

    $this->createDiffArray($dataColumns, $auditColumns);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Return diff columns.
   *
   * @return TableColumnsMetadata
   */
  public function getDiffColumns()
  {
    return $this->diffColumns->getColumns();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Check full and return array without new or obsolete columns if full not set.
   *
   * @param bool $skipObsoleteNew Flag for skipping obsolete and new columns.
   *
   * @return TableColumnsMetadata
   */
  public function removeMatchingColumns($skipObsoleteNew)
  {
    $metadata = $this->diffColumns->getColumns();
    /** @var MultiSourceColumnMetadata $column */
    foreach ($metadata->getColumns() as $columnName => $column)
    {
      /** @var MultiSourceColumnMetadata $data */
      $data = $column->getProperty('data');
      /** @var MultiSourceColumnMetadata $audit */
      $audit = $column->getProperty('audit');
      /** @var MultiSourceColumnMetadata $config */
      $config = $column->getProperty('config');

      $auditColumn = AuditDataLayer::searchInRowSet('column_name', $columnName, $this->configAuditColumns);
      if (!isset($data))
      {
        if (isset($audit) && isset($config))
        {
          $check = $this->compareCollationCharSetName($audit, $config);
          if ($audit->getProperty('column_type')==$config->getProperty('column_type') && $check)
          {
            if (isset($auditColumn))
            {
              $metadata->removeColumn($columnName);
            }
          }
        }
        elseif ($skipObsoleteNew)
        {
          $metadata->removeColumn($columnName);
        }
      }
      else
      {
        if (isset($audit) && isset($config))
        {
          $check = $this->compareCollationCharSetName($data, $audit, $config);
          if ($audit->getProperty('column_type')==$data->getProperty('column_type') && $check)
          {
            $metadata->removeColumn($columnName);
          }
        }
        elseif ($skipObsoleteNew)
        {
          $metadata->removeColumn($columnName);
        }
      }
    }

    return $metadata;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Add not null to audit columns if it not nullable.
   *
   * @param array $theColumns Audit columns.
   *
   * @return array
   */
  private function addNotNull($theColumns)
  {
    $modifiedColumns = [];
    foreach ($theColumns as $column)
    {
      $modifiedColumn = $column;
      $auditColumn    = AuditDataLayer::searchInRowSet('column_name', $modifiedColumn['column_name'], $this->configColumns);
      if (isset($auditColumn))
      {
        if ($modifiedColumn['is_nullable']==='NO')
        {
          $modifiedColumn['column_type'] = sprintf('%s not null', $modifiedColumn['column_type']);
        }
      }
      $modifiedColumns[] = $modifiedColumn;
    }

    return $modifiedColumns;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Compare collations and character set names.
   *
   * @param MultiSourceColumnMetadata      $audit  Column information from audit schema.
   * @param MultiSourceColumnMetadata      $config Column information from config file.
   * @param MultiSourceColumnMetadata|null $data   Column information from data schema.
   *
   * @return bool
   */
  private function compareCollationCharSetName($audit, $config, $data = null)
  {
    $config_character_set_name = $config->getProperty('character_set_name');
    $config_collation_name     = $config->getProperty('collation_name');
    $audit_character_set_name  = $audit->getProperty('character_set_name');
    $audit_collation_name      = $audit->getProperty('collation_name');

    if (isset($data))
    {
      $data_character_set_name = $data->getProperty('character_set_name');
      $data_collation_name     = $data->getProperty('collation_name');

      if ($audit_character_set_name==$data_character_set_name
        && $audit_character_set_name==$config_character_set_name
        && $audit_collation_name==$config_collation_name
        && $audit_collation_name==$data_collation_name
      )
      {
        return true;
      }
    }
    else
    {
      if ($audit_character_set_name==$config_character_set_name && $audit_collation_name==$config_collation_name)
      {
        return true;
      }
    }

    return false;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Get the difference between data and audit tables.
   *
   * @param TableColumnsMetadata $dataColumns  The table columns from data schema.
   * @param TableColumnsMetadata $auditColumns The table columns from audit schema.
   */
  private function createDiffArray($dataColumns, $auditColumns)
  {
    $configColumns     = new TableColumnsMetadata($this->configColumns);
    $this->diffColumns = new DiffTableColumns($configColumns, $auditColumns, $dataColumns);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
