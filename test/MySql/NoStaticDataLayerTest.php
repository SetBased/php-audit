<?php
declare(strict_types=1);

namespace SetBased\Audit\Test\MySql;

use Composer\Autoload\ClassLoader;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;

/**
 * Test StaticDataLayer is not used in the sources of PhpAudit.
 */
class NoStaticDataLayerTest extends TestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The actual test.
   */
  public function testNoStaticDataLayer(): void
  {
    $files = $this->findPhpFiles();
    self::assertNotEmpty($files);

    foreach ($files as $file)
    {
      $source = file_get_contents($file);
      $pos    = strpos($source, 'StaticDataLayer');
      self::assertFalse($pos, sprintf('Found usage of StaticDataLayer in %s', $file));
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Finds all PHP sources files.
   */
  private function findPhpFiles(): array
  {
    // Get the class loader.
    /** @var ClassLoader $loader */
    $loader = spl_autoload_functions()[0][0];

    $audit_data_layer_path = realpath($loader->findFile('SetBased\\Audit\\MySql\\AuditDataLayer'));
    $dir                   = realpath(dirname($audit_data_layer_path).DIRECTORY_SEPARATOR.'..');

    $directory = new RecursiveDirectoryIterator($dir);
    $iterator  = new RecursiveIteratorIterator($directory);
    $regex     = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

    $files = [];
    foreach ($regex as $name => $object)
    {
      $name = realpath($name);
      if ($name!=$audit_data_layer_path)
      {
        $files[] = $name;
      }
    }

    return $files;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
