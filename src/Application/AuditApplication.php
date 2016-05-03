<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\Audit\Application;

use SetBased\Audit\Command\AboutCommand;
use SetBased\Audit\Command\DiffCommand;
use SetBased\Audit\MySql\Command\AuditCommand;
use Symfony\Component\Console\Application;

//----------------------------------------------------------------------------------------------------------------------
/**
 * The Audit program.
 */
class AuditApplication extends Application
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   */
  public function __construct()
  {
    parent::__construct('AuditApplication');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Gets the default commands that should always be available.
   *
   * @return array An array of default Command instances
   */
  protected function getDefaultCommands()
  {
    $commands = parent::getDefaultCommands();

    $commands[] = new AboutCommand();
    $commands[] = new AuditCommand();
    $commands[] = new DiffCommand();

    return $commands;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
