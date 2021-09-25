<?php
declare(strict_types=1);

namespace SetBased\Audit\Command;

use Noodlehaus\Config;
use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Audit\Style\AuditStyle;
use SetBased\Config\TypedConfig;
use SetBased\Stratum\MySql\MySqlDefaultConnector;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;

/**
 * Base command for other commands of AuditApplication.
 */
class BaseCommand extends Command
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The sections in the configurations file.
   *
   * @var array
   */
  private static array $sections = ['database', 'audit_columns', 'additional_sql', 'tables'];

  /**
   * The strong typed configuration reader and writer.
   *
   * @var TypedConfig
   */
  protected TypedConfig $config;

  /**
   * The name of the configuration file.
   *
   * @var string
   */
  protected string $configFileName = '';

  /**
   * The Output decorator.
   *
   * @var AuditStyle
   */
  protected AuditStyle $io;

  /**
   * If set (the default) the config file must be rewritten. Set to false for testing only.
   *
   * @var bool
   */
  protected bool $rewriteConfigFile = true;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Reads configuration parameters from the configuration file.
   */
  public function readConfigFile(): void
  {
    $this->config = new TypedConfig(new Config($this->configFileName));
    $config       = $this->config->getConfig();

    foreach (self::$sections as $key)
    {
      if (!isset($config[$key]))
      {
        $config[$key] = [];
      }
    }

    $credentials = $this->config->getOptString('database.credentials');
    if ($credentials!==null)
    {
      $tmp = new TypedConfig(new Config(dirname($this->configFileName).'/'.$credentials));
      foreach ($tmp->getManArray('database') as $key => $value)
      {
        $config->set('database.'.$key, $value);
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Use for testing only.
   *
   * @param bool $rewriteConfigFile If true the config file must be rewritten. Otherwise the config must not be
   *                                rewritten.
   */
  public function setRewriteConfigFile(bool $rewriteConfigFile)
  {
    $this->rewriteConfigFile = $rewriteConfigFile;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Connects to a MySQL instance.
   */
  protected function connect(): void
  {
    $connector = new MySqlDefaultConnector($this->config->getManString('database.host'),
                                           $this->config->getManString('database.user'),
                                           $this->config->getManString('database.password'),
                                           $this->config->getManString('database.data_schema'),
                                           $this->config->getManInt('database.port', 3306));
    $dl = new AuditDataLayer($connector, $this->io);
    $dl->connect();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Rewrites the config file with updated data.
   */
  protected function rewriteConfig(): void
  {
    // Return immediately when the config file must not be rewritten.
    if (!$this->rewriteConfigFile) return;

    $tables = $this->config->getManArray('tables');
    ksort($tables);

    $config           = new Config($this->configFileName);
    $config['tables'] = $tables;

    $data = [];
    foreach (self::$sections as $key)
    {
      if (!empty($config->get($key)))
      {
        $data[$key] = $config->get($key);
      }
    }

    $this->writeTwoPhases($this->configFileName, json_encode($data, JSON_PRETTY_PRINT));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Writes a file in two phase to the filesystem.
   *
   * First write the data to a temporary file (in the same directory) and than renames the temporary file. If the file
   * already exists and its content is equal to the data that must be written no action  is taken. This has the
   * following advantages:
   * * In case of some write error (e.g. disk full) the original file is kept in tact and no file with partially data
   * is written.
   * * Renaming a file is atomic. So, running processes will never read a partially written data.
   *
   * @param string $filename The name of the file were the data must be stored.
   * @param string $data     The data that must be written.
   */
  protected function writeTwoPhases(string $filename, string $data): void
  {
    $write_flag = true;
    if (file_exists($filename))
    {
      $old_data = file_get_contents($filename);
      if ($data===$old_data) $write_flag = false;
    }

    if ($write_flag)
    {
      $tmp_filename = $filename.'.tmp';
      file_put_contents($tmp_filename, $data);
      rename($tmp_filename, $filename);

      $this->io->text(sprintf('Wrote <fso>%s</fso>', OutputFormatter::escape($filename)));
    }
    else
    {
      $this->io->text(sprintf('File <fso>%s</fso> is up to date', OutputFormatter::escape($filename)));
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
