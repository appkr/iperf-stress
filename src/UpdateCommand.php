<?php

namespace Appkr;

use Herrera\Json\Exception\FileException;
use Herrera\Phar\Update\Manager;
use Herrera\Phar\Update\Manifest;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

class UpdateCommand extends Command
{
    use CommandTrait;

    const MANIFEST_FILE = 'http://appkr.github.io/iperf-stress/manifest.json';

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure() {
        $this->setName('update')
             ->setDescription('Updates iperf-stress.phar binary to the latest version.')
             ->addOption('major', null, InputOption::VALUE_NONE, 'allow major version update');
    }

    /**
     * Execute the command.
     *
     * @return void
     */
    protected function fire() {
        $this->io->title('Looking for updates...');

        try {
            $manager = new Manager(Manifest::loadFile(self::MANIFEST_FILE));
        } catch (FileException $e) {
            $this->io->error('Unable to search for updates');
            return 1;
        }

        $currentVersion = $this->getApplication()->getVersion();
        $allowMajor = $this->option('major');

        if ($manager->update($currentVersion, $allowMajor)) {
            $this->io->success('Updated to latest version');
        } else {
            $this->io->comment('Already up-to-date');
        }
    }
}