<?php
declare(strict_types=1);

namespace SetBased\Audit;

use SetBased\Audit\Metadata\TableMetadata;
use SetBased\Audit\Style\AuditStyle;
use SetBased\Exception\FallenException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;

/**
 * Class for printing the differences between audit and data tables.
 */
class DiffTable
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The audit table.
   *
   * @var TableMetadata
   */
  private TableMetadata $auditTable;

  /**
   * The data table.
   *
   * @var TableMetadata
   */
  private TableMetadata $dataTable;

  /**
   * The join column names of the audit and data table.
   *
   * @var array[]
   */
  private array $rows = [];

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param TableMetadata $dataTable  The data table.
   * @param TableMetadata $auditTable The audit table.
   */
  public function __construct(TableMetadata $dataTable, TableMetadata $auditTable)
  {
    $this->dataTable  = $dataTable;
    $this->auditTable = $auditTable;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @param AuditStyle $io   The IO object.
   * @param bool       $full If false and only if only differences are shown.
   */
  public function print(AuditStyle $io, bool $full): void
  {
    $this->rowsEnhanceWithTableColumns();
    $this->rowsEnhanceWithTableOptions();
    $this->rowsEnhanceWithDiffIndicator();
    $this->rowsEnhanceWithColumnTypeInfo();
    $this->rowsEnhanceWithFormatting();

    if ($full || $this->hasDifferences())
    {
      $io->writeln('<dbo>'.$this->dataTable->getTableName().'</dbo>');

      $table = new Table($io);
      $table->setHeaders(['column', 'audit table', 'config / data table'])
            ->setRows($this->getRows($full));
      $table->render();

      $io->writeln('');
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the rows suitable for Symfony's table.
   *
   * @param bool $full If false and only if only differences are shown.
   *
   * @return array
   */
  private function getRows(bool $full): array
  {
    $ret     = [];
    $options = false;
    foreach ($this->rows as $row)
    {
      // Add separator between columns and options.
      if ($options===false && $row['type']==='option')
      {
        if (!empty($ret)) $ret[] = new TableSeparator();
        $options = true;
      }

      if ($full || $row['diff'])
      {
        $ret[] = [$row['name'], $row['audit1'], $row['data1']];

        if ($row['rowspan']===2)
        {
          $ret[] = ['', $row['audit2'], $row['data2']];
        }
      }
    }

    return $ret;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns true if and only if the audit and data tables have differences.
   *
   * @return bool
   */
  private function hasDifferences(): bool
  {
    foreach ($this->rows as $row)
    {
      if ($row['diff']) return true;
    }

    return false;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Enhances rows with column type info.
   */
  private function rowsEnhanceWithColumnTypeInfo(): void
  {
    foreach ($this->rows as &$row)
    {
      if ($row['type']==='column')
      {
        if ($row['data']!==null)
        {
          $row['data1'] = $row['data']->getTypeInfo1();
          $row['data2'] = $row['data']->getTypeInfo2();
        }
        else
        {
          $row['data1'] = null;
          $row['data2'] = null;
        }

        if ($row['audit']!==null)
        {
          $row['audit1'] = $row['audit']->getTypeInfo1();
          $row['audit2'] = $row['audit']->getTypeInfo2();
        }
        else
        {
          $row['audit1'] = null;
          $row['audit2'] = null;
        }

        $row['rowspan'] = ($row['data2']===null && $row['audit2']===null) ? 1 : 2;
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Enhances rows with diff indicator.
   */
  private function rowsEnhanceWithDiffIndicator(): void
  {
    foreach ($this->rows as &$row)
    {
      switch ($row['type'])
      {
        case 'column':
          $row['diff'] = (isset($row['audit'])!==isset($row['data']) ||
            $row['audit']->getColumnDefinition()!==$row['data']->getColumnDefinition());
          break;

        case 'option':
          $row['diff'] = ($row['audit1']!==$row['data1']);
          break;

        default:
          throw new FallenException('type', $row['type']);
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Enhances rows text with formatting.
   */
  private function rowsEnhanceWithFormatting(): void
  {
    foreach ($this->rows as &$row)
    {
      if ($row['diff'])
      {
        $row['name'] = sprintf('<mm_column>%s</mm_column>', $row['name']);
      }

      if ($row['audit1']!==$row['data1'])
      {
        $row['audit1'] = sprintf('<mm_type>%s</mm_type>', $row['audit1']);
        $row['data1']  = sprintf('<mm_type>%s</mm_type>', $row['data1']);
      }

      if ($row['rowspan']===2 && ($row['audit2']!==$row['data2']))
      {
        $row['audit2'] = sprintf('<mm_type>%s</mm_type>', $row['audit2']);
        $row['data2']  = sprintf('<mm_type>%s</mm_type>', $row['data2']);
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Computes the joins columns of the audit and data table.
   */
  private function rowsEnhanceWithTableColumns(): void
  {
    $auditColumns = $this->auditTable->getColumns()->getColumnNames();
    $dataColumns  = $this->dataTable->getColumns()->getColumnNames();

    $this->rows = [];
    foreach ($dataColumns as $column)
    {
      if (in_array($column, $auditColumns))
      {
        $this->rows[] = ['name'     => $column,
                         'audit'    => $this->auditTable->getColumns()->getColumn($column),
                         'data'     => $this->dataTable->getColumns()->getColumn($column),
                         'type'     => 'column',
                         'new'      => false,
                         'obsolete' => false];
      }
      else
      {
        $this->rows[] = ['name'     => $column,
                         'audit'    => null,
                         'data'     => $this->dataTable->getColumns()->getColumn($column),
                         'type'     => 'column',
                         'new'      => true,
                         'obsolete' => false];
      }
    }

    foreach ($auditColumns as $column)
    {
      if (!in_array($column, $dataColumns))
      {
        $this->rows[] = ['name'     => $column,
                         'audit'    => $this->auditTable->getColumns()->getColumn($column),
                         'data'     => null,
                         'type'     => 'column',
                         'new'      => false,
                         'obsolete' => true];
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Adds table options to the rows.
   */
  private function rowsEnhanceWithTableOptions(): void
  {
    $auditOptions = array_keys($this->auditTable->getOptions());
    $dataOptions  = array_keys($this->dataTable->getOptions());

    foreach ($dataOptions as $option)
    {
      $this->rows[] = ['name'    => $option,
                       'audit1'  => $this->auditTable->getProperty($option),
                       'data1'   => $this->dataTable->getProperty($option),
                       'type'    => 'option',
                       'rowspan' => 1];
    }

    foreach ($auditOptions as $option)
    {
      if (!in_array($option, $dataOptions))
      {
        $this->rows[] = ['name'    => $option,
                         'audit1'  => $this->auditTable->getProperty($option),
                         'data2'   => null,
                         'type'    => 'option',
                         'rowspan' => 1];
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
