<?php
declare(strict_types=1);

namespace SetBased\Audit\Command;

use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Exception\RuntimeException;
use SetBased\Stratum\Style\StratumStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;

/**
 * Base command for other commands of AuditApplication.
 */
class BaseCommand extends Command
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * All config file as array.
   *
   * @var array
   */
  protected $config = [];

  /**
   * The name of the configuration file.
   *
   * @var string
   */
  protected $configFileName = '';

  /**
   * The Output decorator.
   *
   * @var StratumStyle
   */
  protected $io;

  /**
   * If set (the default) the config file must be rewritten. Set to false for testing only.
   *
   * @var bool
   */
  protected $rewriteConfigFile = true;

  //--------------------------------------------------------------------------------------------------------------------

  /**
   * Returns the value of a setting.
   *
   * @param array  $settings    The settings as returned by parse_ini_file.
   * @param bool   $mandatory   If set and setting $settingName is not found in section $sectionName an exception
   *                            will be thrown.
   * @param string $sectionName The name of the section of the requested setting.
   * @param string $settingName The name of the setting of the requested setting.
   *
   * @return null|string
   *
   * @throws RuntimeException
   */
  protected static function getSetting(array $settings,
                                       bool $mandatory,
                                       string $sectionName,
                                       string $settingName): ?string
  {
    // Test if the section exists.
    if (!array_key_exists($sectionName, $settings))
    {
      if ($mandatory)
      {
        throw new RuntimeException("Section '%s' not found in configuration file.", $sectionName);
      }
      else
      {
        return null;
      }
    }

    // Test if the setting in the section exists.
    if (!array_key_exists($settingName, $settings[$sectionName]))
    {
      if ($mandatory)
      {
        throw new RuntimeException("Setting '%s' not found in section '%s' configuration file.",
                                   $settingName,
                                   $sectionName);
      }
      else
      {
        return null;
      }
    }

    return $settings[$sectionName][$settingName];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Reads configuration parameters from the configuration file.
   */
  public function readConfigFile(): void
  {
    $content = file_get_contents($this->configFileName);

    $this->config = (array)json_decode($content, true);
    if (json_last_error()!=JSON_ERROR_NONE)
    {
      throw new RuntimeException("Error decoding JSON: '%s'.", json_last_error_msg());
    }

    if (!isset($this->config['audit_columns']))
    {
      $this->config['audit_columns'] = [];
    }

    if (!isset($this->config['additional_sql']))
    {
      $this->config['additional_sql'] = [];
    }

    if (!isset($this->config['tables']))
    {
      $this->config['tables'] = [];
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
   *
   * @param array $settings The settings from the configuration file.
   */
  protected function connect(array $settings): void
  {
    $host     = $this->getSetting($settings, true, 'database', 'host');
    $user     = $this->getSetting($settings, true, 'database', 'user');
    $password = $this->getSetting($settings, true, 'database', 'password');
    $database = $this->getSetting($settings, true, 'database', 'data_schema');

    AuditDataLayer::setIo($this->io);
    AuditDataLayer::connect($host, $user, $password, $database);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Rewrites the config file with updated data.
   */
  protected function rewriteConfig(): void
  {
    // Return immediately when the config file must not be rewritten.
    if (!$this->rewriteConfigFile) return;

    ksort($this->config['tables']);
    $this->writeTwoPhases($this->configFileName, json_encode($this->config, JSON_PRETTY_PRINT));
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
      if ($data==$old_data) $write_flag = false;
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
