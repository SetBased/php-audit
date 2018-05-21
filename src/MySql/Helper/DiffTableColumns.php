<?php

namespace SetBased\Audit\MySql\Helper;

use SetBased\Audit\MySql\Metadata\TableColumnsMetadata;

/**
 * Class container for multi source column types.
 */
class DiffTableColumns
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Contains all column types from audit and data schemas.
   *
   * @var TableColumnsMetadata
   */
  private $multiSourceColumns = [];

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor
   *
   * @param TableColumnsMetadata $configColumns The table columns from config file.
   * @param TableColumnsMetadata $auditColumns  The table columns from audit schema.
   * @param TableColumnsMetadata $dataColumns   The table columns from data schema.
   */
  public function __construct($configColumns, $auditColumns, $dataColumns)
  {
    $this->appendColumnTypes(['config' => $configColumns, 'audit' => $auditColumns, 'data' => $dataColumns]);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Get columns types.
   *
   * @return TableColumnsMetadata
   */
  public function getColumns()
  {
    return $this->multiSourceColumns;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Add to array all columns types.
   *
   * @param TableColumnsMetadata[] $allTypes The metadata of the column.
   */
  private function appendColumnTypes($allTypes)
  {
    $allColumnNames = [];

    foreach ($allTypes as $source => $columns)
    {
      $data = array_keys($allTypes[$source]->getColumns());
      foreach ($data as $columnName)
      {
        if (!isset($allColumnNames[$columnName]))
        {
          $allColumnNames[$columnName] = $columnName;
        }
      }
    }

    $multiSourceColumns = [];
    foreach ($allColumnNames as $columnName => $columnData)
    {
      $multiSourceColumn = [];
      foreach ($allTypes as $typePrefix => $typesArray)
      {
        $columns = $typesArray->getColumns();
        if (isset($columns[$columnName]))
        {
          $multiSourceColumn['column_name'] = $columnName;
          $multiSourceColumn[$typePrefix]   = $columns[$columnName];
        }
      }
      $multiSourceColumns[$columnName] = $multiSourceColumn;
    }
    $this->multiSourceColumns = new TableColumnsMetadata($multiSourceColumns, 'MultiSourceColumnMetadata');
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
