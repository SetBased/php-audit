<?php

namespace SetBased\Audit\MySql\Helper;

use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Audit\MySql\Metadata\MultiSourceColumnMetadata;
use SetBased\Audit\MySql\Metadata\TableColumnsMetadata;
use Symfony\Component\Console\Helper\TableSeparator;

/**
 * A helper class for creating printing Tables.
 */
class DiffTableHelper
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Audit columns from config file.
   *
   * @var array
   */
  private $auditColumns;

  /**
   * Audit options from audit schema.
   *
   * @var array
   */
  private $auditTableOptions;

  /**
   * Audit options from data schema.
   *
   * @var array
   */
  private $dataTableOptions;

  /**
   * Check existing separator.
   *
   * @var bool
   */
  private $existSeparator = false;

  /**
   * Full option.
   *
   * @var bool
   */
  private $fullOption;

  /**
   * Array with rows for table.
   *
   * @var array[]
   */
  private $rows = [];

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param string  $dataSchema      Data schema name.
   * @param string  $auditSchema     Audit schema name.
   * @param string  $tableName       The table name.
   * @param array[] $theAuditColumns Audit columns from config file.
   * @param bool    $fullOption      If set append table options to rows.
   */
  public function __construct($dataSchema, $auditSchema, $tableName, $theAuditColumns, $fullOption)
  {
    $this->auditColumns      = $theAuditColumns;
    $this->fullOption        = $fullOption;
    $this->auditTableOptions = AuditDataLayer::getTableOptions($auditSchema, $tableName);
    $this->dataTableOptions  = AuditDataLayer::getTableOptions($dataSchema, $tableName);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Add highlighting to columns.
   */
  public function addHighlighting()
  {
    $styledColumns = [];
    foreach ($this->rows as $key => $column)
    {
      $styledColumn = $column;
      if (is_array($column))
      {
        $auditColumn = AuditDataLayer::searchInRowSet('column_name', $column['column_name'], $this->auditColumns);
        // Highlighting for data table column types and audit.
        if (!empty($column['data']))
        {
          if (isset($column['data']) && !isset($column['audit']))
          {
            if (!isset($column['column_name']))
            {
              $styledColumns[$key - 1]['column_name'] = sprintf('<mm_column>%s</>', $styledColumns[$key - 1]['column_name']);
            }
            $styledColumn['column_name'] = sprintf('<mm_column>%s</>', $styledColumn['column_name']);
            $styledColumn['data']        = sprintf('<mm_type>%s</>', $styledColumn['data']);
          }
          else if (!isset($column['data']) && isset($column['audit']))
          {
            $styledColumn['audit'] = sprintf('<mm_type>%s</>', $styledColumn['audit']);
          }
          else if (strcmp($column['data'], $column['audit']))
          {
            if (!isset($column['column_name']))
            {
              $styledColumns[$key - 1]['column_name'] = sprintf('<mm_column>%s</>', $styledColumns[$key - 1]['column_name']);
            }
            $styledColumn['column_name'] = sprintf('<mm_column>%s</>', $styledColumn['column_name']);
            $styledColumn['data']        = sprintf('<mm_type>%s</>', $styledColumn['data']);
            $styledColumn['audit']       = sprintf('<mm_type>%s</>', $styledColumn['audit']);
          }
          if(isset($styledColumn['config']))
          {
            if (strcmp($styledColumn['audit'], $styledColumn['config']))
            {
              if (!isset($column['column_name']))
              {
                $styledColumns[$key - 1]['column_name'] = sprintf('<mm_column>%s</>', $styledColumns[$key - 1]['column_name']);
              }
              $styledColumn['column_name'] = sprintf('<mm_column>%s</>', $styledColumn['column_name']);
              $styledColumn['audit']       = sprintf('<mm_type>%s</>', $column['audit']);
              $styledColumn['config']      = sprintf('<mm_type>%s</>', $styledColumn['config']);
            }
          }
        }
        else
        {
          if (!isset($column['data']) && isset($column['audit']) && !isset($auditColumn))
          {
            $styledColumn['audit'] = sprintf('<mm_type>%s</>', $styledColumn['audit']);
          }
          // Highlighting for audit table column types and audit_columns in config file.
          $searchColumn = AuditDataLayer::searchInRowSet('column_name', $styledColumn['column_name'], $this->auditColumns);
          if (isset($searchColumn))
          {
            $configType = $this->auditColumns[$searchColumn]['column_type'];
            if (isset($configType) && !isset($column['audit']))
            {
              $styledColumn['column_name'] = sprintf('<mm_column>%s</>', $styledColumn['column_name']);
              $styledColumn['config']      = sprintf('<mm_type>%s</>', $styledColumn['config']);
            }
            else if (!isset($configType) && isset($column['audit']))
            {
              $styledColumn['audit'] = sprintf('<mm_type>%s</>', $column['audit']);
            }
            else if (strcmp($configType, $column['audit']))
            {
              $styledColumn['column_name'] = sprintf('<mm_column>%s</>', $styledColumn['column_name']);
              $styledColumn['audit']       = sprintf('<mm_type>%s</>', $column['audit']);
              $styledColumn['config']      = sprintf('<mm_type>%s</>', $styledColumn['config']);
            }
          }
          else
          {
            if (strcmp($styledColumn['audit'], $styledColumn['config']))
            {
              if (!isset($column['column_name']))
              {
                $styledColumns[$key - 1]['column_name'] = sprintf('<mm_column>%s</>', $styledColumns[$key - 1]['column_name']);
              }
              $styledColumn['column_name'] = sprintf('<mm_column>%s</>', $styledColumn['column_name']);
              $styledColumn['audit']       = sprintf('<mm_type>%s</>', $column['audit']);
              $styledColumn['config']      = sprintf('<mm_type>%s</>', $styledColumn['config']);
            }
          }
        }
      }
      $styledColumns[] = $styledColumn;
    }

    $this->rows = $styledColumns;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Appends rows.
   *
   * @param TableColumnsMetadata $rows Rows array.
   */
  public function appendRows($rows)
  {
    /** @var MultiSourceColumnMetadata $rowMetadata */
    foreach ($rows->getColumns() as $columnName => $rowMetadata)
    {
      DiffTableRowHelper::appendRow($this->rows, $rowMetadata, $columnName);
    }
    $this->appendTableOption('engine');
    $this->appendTableOption('character_set_name', 'character set');
    $this->appendTableOption('table_collation', 'collation');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Append row with table option.
   *
   * @param string      $option The option.
   * @param null|string $name   Display name.
   */
  public function appendTableOption($option, $name = null)
  {
    if ($this->dataTableOptions[$option]!=$this->auditTableOptions[$option] || $this->fullOption)
    {
      if (!$this->existSeparator)
      {
        $this->rows[]         = new TableSeparator();
        $this->existSeparator = true;
      }
      if ($name===null)
      {
        $name = $option;
      }
      $tableRow            = ['column_name' => $name,
                              'data'        => $this->dataTableOptions[$option],
                              'audit'       => $this->auditTableOptions[$option],
                              'config'      => null];
      $this->rows[$option] = $tableRow;
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Get rows.
   *
   * @return array[]
   */
  public function getRows()
  {
    return $this->rows;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
