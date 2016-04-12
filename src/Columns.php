<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\Audit;

//----------------------------------------------------------------------------------------------------------------------
/**
 * Class for metadata of (table) columns.
 */
class Columns
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The metadata of the columns.
   *
   * @var array[]
   */
  private $myColumns = [];

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param array[] $theColumns The metadata of the columns.
   */
  public function __construct($theColumns)
  {
    reset($theColumns);
    $first_key = key($theColumns);
    foreach ($theColumns as $key => $column)
    {
      $this->myColumns[$column['column_name']] = [
        'column_name'      => $column['column_name'],
        'column_type'      => $column['column_type'],
        'audit_expression' => isset($column['expression']) ? $column['expression'] : null,
        'audit_value_type' => isset($column['value_type']) ? $column['value_type'] : null,
        'after'            => ($key===$first_key) ? null : $theColumns[$key - 1]['column_name']
      ];
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Generate array with audit columns and columns from data table.
   *
   * @param Columns $theAuditColumnsMetadata   Audit columns for adding to exist columns
   * @param Columns $theCurrentColumnsMetadata Exist table columns
   *
   * @return Columns
   */
  public static function combine($theAuditColumnsMetadata, $theCurrentColumnsMetadata)
  {
    $columns = [];

    foreach ($theAuditColumnsMetadata->getColumns() as $column)
    {
      $columns[] = ['column_name' => $column['column_name'], 'column_type' => $column['column_type']];
    }

    foreach ($theCurrentColumnsMetadata->getColumns() as $column)
    {
      if ($column['column_type']!='timestamp')
      {
        $columns[] = ['column_name' => $column['column_name'], 'column_type' => $column['column_type'].' DEFAULT NULL'];
      }
      else
      {
        $columns[] = ['column_name' => $column['column_name'], 'column_type' => $column['column_type'].' NULL'];
      }
    }

    return new Columns($columns);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Compares two Columns objects and returns an array with columns that are in the first columns object and in the
   * second Columns object but have different types.
   *
   * @param Columns $theColumns1 The first Columns object.
   * @param Columns $theColumns2 The second Columns object.
   *
   * @return array[]
   */
  public static function differentColumnTypes($theColumns1, $theColumns2)
  {
    $diff = [];
    foreach ($theColumns2->myColumns as $column2)
    {
      if (isset($theColumns1->myColumns[$column2['column_name']]))
      {
        $column1 = $theColumns1->myColumns[$column2['column_name']];
        if ($column2['column_type']!=$column1['column_type'])
        {
          $diff[] = $column1;
        }
      }
    }

    return $diff;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Compares two Columns objects and returns an array with columns that are in the first columns object but not in the
   * second Columns object.
   *
   * @param Columns $theColumns1 The first Columns object.
   * @param Columns $theColumns2 The second Columns object.
   *
   * @return array[]
   */
  public static function notInOtherSet($theColumns1, $theColumns2)
  {
    $diff = [];
    if (isset($theColumns1))
    {
      foreach ($theColumns1->myColumns as $column1)
      {
        if (!isset($theColumns2->myColumns[$column1['column_name']]))
        {
          $diff[] = ['column_name' => $column1['column_name'],
                     'column_type' => $column1['column_type'],
                     'after'       => $column1['after']];
        }
      }
    }

    return $diff;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the underlying array with metadata of the columns.
   *
   * @return array[]
   */
  public function getColumns()
  {
    return $this->myColumns;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
