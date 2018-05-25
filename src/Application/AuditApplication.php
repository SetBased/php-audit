<?php

namespace SetBased\Audit\Application;

use SetBased\Audit\Command\AlterAuditTableCommand;
use SetBased\Audit\Command\AuditCommand;
use SetBased\Audit\Command\DiffCommand;
use SetBased\Audit\Command\DropTriggersCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

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

    $commands[] = new AuditCommand();
    $commands[] = new DiffCommand();
    $commands[] = new DropTriggersCommand();
    $commands[] = new AlterAuditTableCommand();

    return $commands;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
