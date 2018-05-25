<?php

namespace SetBased\Audit\Test\MySql\AuditCommand\MinimalConfig;

use SetBased\Audit\Command\AuditCommand;
use SetBased\Audit\Test\MySql\AuditTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests for/with minimal configuration.
 */
class MinimalConfigTest extends AuditTestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  public function test01()
  {
    $application = new Application();
    $application->add(new AuditCommand());

    /** @var \SetBased\Audit\Command\AuditCommand $command */
    $command = $application->find('audit');
    $command->setRewriteConfigFile(false);
    $commandTester = new CommandTester($command);
    $commandTester->execute(['command'     => $command->getName(),
                             'config file' => __DIR__.'/config/audit.json']);

    self::assertSame(0, $commandTester->getStatusCode());
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
