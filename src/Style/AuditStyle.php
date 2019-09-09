<?php
declare(strict_types=1);

namespace SetBased\Audit\Style;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Output decorator based on Symfony Style Guide.
 */
class AuditStyle extends SymfonyStyle
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  public function __construct(InputInterface $input, OutputInterface $output)
  {
    parent::__construct($input, $output);

    // Create style notes.
    $style = new OutputFormatterStyle('yellow');
    $output->getFormatter()->setStyle('note', $style);

    // Create style for database objects.
    $style = new OutputFormatterStyle('green', null, ['bold']);
    $output->getFormatter()->setStyle('dbo', $style);

    // Create style for file and directory names.
    $style = new OutputFormatterStyle(null, null, ['bold']);
    $output->getFormatter()->setStyle('fso', $style);

    // Create style for SQL statements.
    $style = new OutputFormatterStyle('magenta', null, ['bold']);
    $output->getFormatter()->setStyle('sql', $style);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Logs a message if verbosity is OutputInterface::VERBOSITY_NORMAL or higher.
   *
   * This method takes arguments like sprintf.
   */
  public function logInfo()
  {
    if ($this->getVerbosity()>=OutputInterface::VERBOSITY_NORMAL)
    {
      $args   = func_get_args();
      $format = array_shift($args);

      $this->writeln(vsprintf('<info>'.$format.'</info>', $args));
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Logs a message if verbosity is OutputInterface::VERBOSITY_VERBOSE or higher.
   *
   * This method takes arguments like sprintf.
   */
  public function logVerbose()
  {
    if ($this->getVerbosity()>=OutputInterface::VERBOSITY_VERBOSE)
    {
      $args   = func_get_args();
      $format = array_shift($args);

      $this->writeln(vsprintf('<info>'.$format.'</info>', $args));
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Logs a message if verbosity is OutputInterface::VERBOSITY_VERY_VERBOSE or higher.
   *
   * This method takes arguments like sprintf.
   */
  public function logVeryVerbose()
  {
    if ($this->getVerbosity()>=OutputInterface::VERBOSITY_VERY_VERBOSE)
    {
      $args   = func_get_args();
      $format = array_shift($args);

      $this->writeln(vsprintf('<info>'.$format.'</info>', $args));
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
