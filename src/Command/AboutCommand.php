<?php
//--------------------------------------------------------------------------------------------------------------------
namespace SetBased\Audit\Command;

use SetBased\Stratum\Style\StratumStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

//--------------------------------------------------------------------------------------------------------------------
/**
 * Command showing short information about AuditApplication.
 */
class AboutCommand extends BaseCommand
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function configure()
  {
    $this->setName('about')
         ->setDescription('Short information about AuditApplication')
         ->setHelp('<info>audit about</info>');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->io = $this->io = new StratumStyle($input, $output);

    $this->io->write(<<<EOT
<info>AuditApplication - Database Auditing</info>
<comment>Creates audit tables and triggers to track data changes in databases.</comment>
EOT
    );
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
