<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\Audit\Application;

use SetBased\Audit\Command\AboutCommand;
use SetBased\Audit\MySql\Command\AuditCommand;
use SetBased\Audit\MySql\Command\DiffCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

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
   * @return Command[] An array of default Command instances.
   */
  protected function getDefaultCommands()
  {
    $commands = parent::getDefaultCommands();

    $commands[] = new AboutCommand();
    $commands[] = new AuditCommand();
    $commands[] = new DiffCommand();

//    $commands[] = new ConfigCommand();

    return $commands;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
