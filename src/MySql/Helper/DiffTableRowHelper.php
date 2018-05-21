<?php

namespace SetBased\Audit\MySql\Helper;

use SetBased\Audit\MySql\Metadata\ColumnMetadata;
use SetBased\Audit\MySql\Metadata\MultiSourceColumnMetadata;

/**
 * A helper class for DiffTable rows.
 */
class DiffTableRowHelper
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Append a row to existing rows array.
   *
   * @param array[]                  $theExistRows Exist rows array for appending.
   * @param MultiSourceColumnMetadata $rowMetadata  Row for append.
   * @param string                    $columnName   The columns name.
   */
  public static function appendRow(&$theExistRows, $rowMetadata, $columnName)
  {
    $theExistRows[] = self::createTableRow($rowMetadata, $columnName);
    if (self::checkOptions($rowMetadata))
    {
      $theRow = self::createColumnOptionsRow($rowMetadata);

      $theExistRows[] = $theRow;
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Create additional row for column with character set name and collation name.
   *
   * @param MultiSourceColumnMetadata $rowMetadata Data for table row.
   *
   * @return array<string,null|string>
   */
  public static function createColumnOptionsRow($rowMetadata)
  {
    $columnProperties = $rowMetadata->getProperties();
    /** @var ColumnMetadata $data */
    $data = isset($columnProperties['data']) ? $columnProperties['data'] : null;
    /** @var ColumnMetadata $audit */
    $audit = isset($columnProperties['audit']) ? $columnProperties['audit'] : null;
    /** @var ColumnMetadata $config */
    $config = isset($columnProperties['config']) ? $columnProperties['config'] : null;

    $dataMetadata   = isset($data) ? $data->getProperties() : null;
    $auditMetadata  = isset($audit) ? $audit->getProperties() : null;
    $configMetadata = isset($config) ? $config->getProperties() : null;

    $dataCharsetName   = isset($dataMetadata['character_set_name']) ? $dataMetadata['character_set_name'] : null;
    $dataCollationName = isset($dataMetadata['collation_name']) ? $dataMetadata['collation_name'] : null;

    $auditCharsetName   = isset($auditMetadata['character_set_name']) ? $auditMetadata['character_set_name'] : null;
    $auditCollationName = isset($auditMetadata['collation_name']) ? $auditMetadata['collation_name'] : null;

    $configCharsetName   = isset($configMetadata['character_set_name']) ? $configMetadata['character_set_name'] : null;
    $configCollationName = isset($configMetadata['collation_name']) ? $configMetadata['collation_name'] : null;

    return ['column_name' => null,
            'data'        => self::styledOptionsRow($dataCharsetName, $dataCollationName),
            'audit'       => self::styledOptionsRow($auditCharsetName, $auditCollationName),
            'config'      => self::styledOptionsRow($configCharsetName, $configCollationName)];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Create table row for columns with information from all schemas and config file.
   *
   * @param MultiSourceColumnMetadata $rowMetadata Data for table row.
   * @param string                    $columnName  The columns name.
   *
   * @return array<string,null|string>
   */
  public static function createTableRow($rowMetadata, $columnName)
  {
    $columnProperties = $rowMetadata->getProperties();
    /** @var ColumnMetadata $data */
    $data = isset($columnProperties['data']) ? $columnProperties['data'] : null;
    /** @var ColumnMetadata $audit */
    $audit = isset($columnProperties['audit']) ? $columnProperties['audit'] : null;
    /** @var ColumnMetadata $config */
    $config = isset($columnProperties['config']) ? $columnProperties['config'] : null;

    $dataMetadata   = isset($data) ? $data->getProperties() : null;
    $auditMetadata  = isset($audit) ? $audit->getProperties() : null;
    $configMetadata = isset($config) ? $config->getProperties() : null;

    return ['column_name' => $columnName,
            'data'        => isset($dataMetadata['column_type']) ? $dataMetadata['column_type'] : null,
            'audit'       => isset($auditMetadata['column_type']) ? $auditMetadata['column_type'] : null,
            'config'      => isset($configMetadata['column_type']) ? $configMetadata['column_type'] : null];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Helper function for creating string with character set name and collation name.
   *
   * @param string $theCharacterSetName Character set name
   * @param string $theCollationName    Collation name
   *
   * @return string
   */
  public static function styledOptionsRow($theCharacterSetName, $theCollationName)
  {
    $charsetName   = isset($theCharacterSetName) ? '['.$theCharacterSetName.']' : null;
    $collationName = isset($theCollationName) ? '['.$theCollationName.']' : null;

    return trim(sprintf('%s %s', $charsetName, $collationName));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Check isset options(collation, character set name) from row.
   *
   * @param MultiSourceColumnMetadata $rowMetadata Row for append.
   *
   * @return bool
   */
  private static function checkOptions($rowMetadata)
  {
    $columnProperties = $rowMetadata->getProperties();
    foreach ($rowMetadata->getProperties() as $sourceName => $metadata)
    {
      /** @var ColumnMetadata $source */
      $source = isset($columnProperties[$sourceName]) ? $columnProperties[$sourceName] : null;
      $data   = isset($source) ? $source->getProperties() : null;
      if (isset($data['character_set_name']) || isset($data['collation_name']))
      {
        return true;
      }
    }

    return false;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
