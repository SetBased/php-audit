#!/usr/bin/env php
<?php
declare(strict_types=1);

use SetBased\ErrorHandler\ErrorHandler;
use SetBased\Stratum\MySql\MySqlDataLayer;
use SetBased\Stratum\MySql\MySqlDefaultConnector;

//----------------------------------------------------------------------------------------------------------------------
$files = [__DIR__.'/../vendor/autoload.php',
          __DIR__.'/../../vendor/autoload.php',
          __DIR__.'/../../../vendor/autoload.php',
          __DIR__.'/../../../../vendor/autoload.php',
          __DIR__.'/../../../../../vendor/autoload.php'];

foreach ($files as $file)
{
  if (file_exists($file))
  {
    require $file;
    break;
  }
}

declare(ticks=1);

//----------------------------------------------------------------------------------------------------------------------
function signalHandler()
{
  $GLOBALS['exit'] = true;
}

//----------------------------------------------------------------------------------------------------------------------
$GLOBALS['exit'] = false;

pcntl_signal(SIGUSR1, "signalHandler");

$handler = new ErrorHandler();
$handler->registerErrorHandler();

$connector = new MySqlDefaultConnector('127.0.0.1', 'test', 'test', 'test_data');
$dl        = new MySqlDataLayer($connector);
$dl->connect();

while (true)
{
  if ($GLOBALS['exit']) break;

  $dl->begin();
  $dl->executeNone('insert into TABLE1(c) values(1)');
  $dl->executeNone('update TABLE1 set c = 2');
  $dl->executeNone('delete from TABLE1 where c = 2');
  $dl->commit();
}

$dl->disconnect();
