<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Command line for behat_2jmx
 *
 * @package    moodlehq_behat_2jmx
 * @copyright  2015 Rajesh Taneja <rajesh@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace moodlehq\behat_2jmx;

require_once(__DIR__.'/util.php');

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use moodlehq\behat_2jmx\util;

if (isset($_SERVER['REMOTE_ADDR'])) {
    die(); // No access from web!.
}

class generator_command extends Command {
    /**
     * Configure behat generator command.
     */
    protected function configure()  {
        $this
            ->setName("generator")
            ->setDescription('Generate moodle data')
            ->addOption(
                'testplan',
                't',
                InputOption::VALUE_REQUIRED,
                'Create JMX test plan (xs, s, m, l, xl)'
            )
            ->addOption(
                'proxyurl',
                'u',
                InputOption::VALUE_REQUIRED,
                'BrowserMobProxy url should be given with createtestplan (ex: localhost:9090)'
            )
            ->addOption(
                'proxyport',
                'p',
                InputOption::VALUE_REQUIRED,
                'Port on which BrowserMobProxy should listen. If not passed then it will use random port.'
            )
            ->addOption(
                'moodlepath',
                null,
                InputOption::VALUE_REQUIRED,
                'Path of moodle source to use.'
            )
            ->addOption(
                'datapath',
                null,
                InputOption::VALUE_REQUIRED,
                'Path of directory where moodle state will be saved'
            )
            ->addOption(
                'value',
                null,
                InputOption::VALUE_REQUIRED,
                'Output which value you want to return, version|moodlepath|datapath'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Used with force genertion of testplan.'
            )
        ;
    }

    /**
     * Ask user about moodle and data path for storing moodle state.
     *
     * @param OutputInterface $output
     * @return array
     */
    private function ask_install_dirs(OutputInterface $output) {
        $dialog = $this->getHelperSet()->get('dialog');
        while (!$moodlepath = $dialog->ask($output, '<question>Path of your moodle source code: </question>')) {
            // Keep looping till you don't get proper path from user.
        }
        while (!$datapath = $dialog->ask($output, '<question>Directory path to store data: </question>')) {
            // Keep looping till you don't get proper path from user.
        }

        return array($moodlepath, $datapath);
    }

    /**
     * Interacts with the user.
     *
     * This method is executed before the InputDefinition is validated.
     * This means that this is the only place where the command can
     * interactively ask for values of missing required arguments.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function interact(InputInterface $input, OutputInterface $output) {
        global $CFG;

        // If we can write to current directory then use it by default for moodlepath and datapath. Else put this in data folder.
        $testplanjson = util::get_json_config_path(true);

        // Make sure we have moodlepath and datapath, before excuting any option.
        if (!$input->getOption('moodlepath') || !$input->getOption('datapath')) {
            // Check if it's already set, if not then ask user.
            if (file_exists($testplanjson)) {
                $configjson = file_get_contents($testplanjson);
                $configjson = json_decode($configjson, true);
                if (isset($configjson['config']['moodlepath']) && isset($configjson['config']['datapath'])) {
                    return array($configjson['config']['moodlepath'], $configjson['config']['datapath']);
                }
            }

            $dialog = $this->getHelperSet()->get('dialog');

            list($moodlepath, $datapath) = $this->ask_install_dirs($output);
            while (!$dialog->askConfirmation($output, '<question>Are you sure to use following paths</question>'.PHP_EOL.'
                <info>Moodle path: '.$moodlepath.'</info>'.PHP_EOL.'<info>Data path:'.$datapath.' (Y/N): </info>')) {

                // Keep asking user till we get final input.
                list($moodlepath, $datapath) = $this->ask_install_dirs($output);
            }
        } else {
            $moodlepath = $input->getOption('moodlepath');
            $datapath = $input->getOption('datapath');
        }

        // Get the config if it exists or default and update moodlepath and datapath.
        if (file_exists($testplanjson)) {
            $configjson = file_get_contents($testplanjson);
            $configjson = json_decode($configjson, true);
        } else {
            $testplanjsondist = __DIR__ . '/../testplan.json-dist';
            $configjson = file_get_contents($testplanjsondist);
            $configjson = json_decode($configjson, true);
        }

        $configjson['config'] = array(
            'moodlepath' => $moodlepath,
            'datapath' => $datapath,
        );

        // Save final config with install paths.
        file_put_contents($testplanjson, json_encode($configjson));

    }

    /**
     * Execute the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) {

        // We don't need moodle for getting value.
        if ($value = $input->getOption('value')) {
            switch ($value) {
                case 'version':
                    $output->writeln(\moodlehq\behat_2jmx\util::get_tool_version());
                    break;
                case 'moodlepath':
                    $output->writeln(util::get_moodle_path());
                    break;
                case 'datapath':
                    $output->writeln(util::get_data_path());
                    break;
                default:
                    $output->writeln('<error>Not a valid option.</error><info> Should be wither version|moodlepath|datapath</info>');
                    return 1;
                    break;
            }
            return 0;
        }

        // Describe this script.
        define('PERFORMANCE_SITE_GENERATOR', true);
        define('CLI_SCRIPT', true);
        define('NO_OUTPUT_BUFFERING', true);
        define('IGNORE_COMPONENT_CACHE', true);

        // Load moodle config and all classes.
        $moodlepath = util::get_moodle_path();
        // Autoload files and ensure we load moodle config, as we will be using moodle code for behat context.
        require_once($moodlepath . '/config.php');
        require_once(__DIR__ . '/inc.php');

        raise_memory_limit(MEMORY_HUGE);
        $status = false;

        // Do action.
        $behat2jmx = new \moodlehq\behat_2jmx\generator();

        $testplan = $input->getOption('testplan');
        $proxyurl = $input->getOption('proxyurl');

        // make sure proxyport and proxy url is provided.
        if (empty($proxyurl) || empty($testplan)) {
            $output->write($this->getHelp(), true);
            return 1;
        }

        $status = $behat2jmx->create_test_plan(strtolower($testplan), $proxyurl,
            $input->getOption('proxyport'), $input->getOption('force'));

        return $status;
    }

    /**
     * Gets the help message.
     *
     * @return string A help message.
     */
    public function getHelp() {
        $help = "
<error>This script have behat => jmx utility.</error>
<question>Have you started</question>
<info>- browsermob proxy</info>
<info>- Selenium server</info>

Usage to create test plan:
  <comment>vendor/bin/moodle_behat_2jmx [--testplan=PlanSize --proxyurl=proxyurl|--proxyport=9090|--enable]</comment>

Options:
<info>
--testplan  | -t Create JMX test plan (xs, s, m, l, xl)
--proxyurl  | -u BrowserMobProxy url should be given with createtestplan (ex: localhost:9090)
--proxyport | -p Port on which BrowserMobProxy should listen. If not passed then it will use random port.
--moodlepath     Path of moodle source
--datapath       Path to dir (different from moodle dataroot), to store test data.
--value          Output which value you check, version|moodlepath|datapath
--force          To run behat-2jmx commad without inetraction, use this option. This only applies for default features,
                 if you have new feature and doesn't have associated config request filled, then don't use this option.

-h, --help Print out this help
</info>

Example from Moodle root directory:
<comment>
\$ vendor/bin/moodle_behat_2jmx --testplan S --proxyurl localhost:9090
</comment>
More info in http://docs.moodle.org/dev/Performance_testing#Running_tests
";
        return $help;
    }
}
