<?php

namespace SetBased\Audit\MySql\Command;

use SetBased\Audit\Command\BaseCommand;
use SetBased\Audit\MySql\AuditDataLayer;

/**
 * Base class for commands which needs to connect to a MySQL instance.
 */
class MySqlBaseCommand extends BaseCommand
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Disconnects from MySQL instance.
   */
  public function disconnect()
  {
    AuditDataLayer::disconnect();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Connects to a MySQL instance.
   *
   * @param array $settings The settings from the configuration file.
   */
  protected function connect($settings)
  {
    $host     = $this->getSetting($settings, true, 'database', 'host');
    $user     = $this->getSetting($settings, true, 'database', 'user');
    $password = $this->getSetting($settings, true, 'database', 'password');
    $database = $this->getSetting($settings, true, 'database', 'data_schema');

    AuditDataLayer::setIo($this->io);
    AuditDataLayer::connect($host, $user, $password, $database);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
