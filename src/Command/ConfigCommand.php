<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\Audit\Command;

use SetBased\Stratum\Style\StratumStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

//----------------------------------------------------------------------------------------------------------------------
/**
 * Command for editing data in config file.
 */
class ConfigCommand extends BaseCommand
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The helper instance by name.
   */
  private $helper;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this->setName('config')
         ->setDescription('Create or edit config file')
         ->addArgument('config file', InputArgument::OPTIONAL, 'The audit configuration file', 'etc/audit.json');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->io = new StratumStyle($input, $output);

    $this->configFileName = $input->getArgument('config file');
    $this->readConfigFile();
    $this->configFileName = sprintf('%s%s', $input->getArgument('config file'), '.new');

    $this->helper = $this->getHelper('question');

    foreach ($this->config as $configPart => $partData)
    {
      $this->setConfigPart($input, $output, $configPart);
    }

    $this->writeTwoPhases($this->configFileName, json_encode($this->config, JSON_PRETTY_PRINT));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Controls config parts.
   *
   * @param InputInterface  $input
   * @param OutputInterface $output
   * @param string          $configPart Part of config file.
   */
  protected function setConfigPart(InputInterface $input, OutputInterface $output, $configPart)
  {
    switch ($configPart)
    {
      case 'database':
        $this->setDatabasePart($input, $output, $configPart);
        break;
      case 'tables':
        foreach ($this->config[$configPart] as $tableName => $tableData)
        {
          $this->setTableParams($input, $output, $configPart, $tableName);
        }
        break;
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Configure each table for audit or not.
   *
   * @param InputInterface  $input
   * @param OutputInterface $output
   * @param string          $configPart Part of config file.
   * @param string          $tableName  The table name.
   */
  protected function setTableParams(InputInterface $input, OutputInterface $output, $configPart, $tableName)
  {
    $this->io->logInfo('Please input data for table <dbo>\'%s\'</dbo>.', $tableName);
    $question = sprintf('Audit table \'%s\' or not (y|(n)): ', $tableName);
    $question = new ConfirmationQuestion($question, false);

    $this->config[$configPart][$tableName]['audit'] = $this->helper->ask($input, $output, $question);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Configure database part.
   *
   * @param InputInterface  $input
   * @param OutputInterface $output
   * @param string          $configPart Part of config file.
   */
  protected function setDatabasePart(InputInterface $input, OutputInterface $output, $configPart)
  {
    $this->io->logInfo('Please input data for <dbo>\'%s\'</dbo> part.', $configPart);
    foreach ($this->config[$configPart] as $parameter => $value)
    {
      $question                              = sprintf('Please enter the \'%s\' (%s): ', $parameter, $value);
      $question                              = new Question($question, $value);
      $this->config[$configPart][$parameter] = $this->helper->ask($input, $output, $question);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
