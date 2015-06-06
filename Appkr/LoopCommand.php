<?php namespace Appkr;

use Carbon\Carbon;
use History;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class LoopCommand extends Command{

    use CommandTrait, EloquentTrait;

    /**
     * System command to run
     * @var string
     */
    private $command = null;

    /**
     * Regex pattern for $this->result parsing
     * @var string
     */
    private $pattern = '/^\[\s*\d*\]\s*[0-9-.]+\s*(sec)\s*(?P<data>[\d.]+)\s*(GBytes|MBytes|KBytes)\s*(?P<speed>[\d.]+)\s*(?P<unit>(Gbits\/sec|Mbits\/sec|Kbits\/sec))/';

    /**
     * iperf result string
     * @var string
     */
    private $result = null;

    /**
     * Regex result of $this->result
     * @var array
     */
    private $matches = [];

    /**
     * Constructor
     */
    public function __construct() {
        $this->bootDatabase();

        parent::__construct();
    }

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure() {
        $this->setName('loop')
            ->setDescription('Loop the test.')
            ->addArgument(
                'count',
                InputArgument::OPTIONAL,
                'number of loop. acceptable maximum value is 1000',
                1
            )
            ->addOption(
                'sleep',
                'S',
                InputOption::VALUE_OPTIONAL,
                'sleep between loops in sec',
                1
            )
            ->addOption(
                'no-reverse',
                'R',
                InputOption::VALUE_NONE,
                "turn off reverse mode. if provided system's iperf is used"
            )
            ->addOption(
                'client',
                'c',
                InputOption::VALUE_REQUIRED,
                "run in client mode, connecting to <host>",
                'speedgiga1.airplug.com'
            )
            //->addOption(
            //'interval',
            // 'i',
            // InputOption::VALUE_OPTIONAL,
            // 'seconds between periodic bandwidth reports',
            // 0.5
            //)
            ->addOption(
                'len',
                'l',
                InputOption::VALUE_OPTIONAL,
                'length of buffer to read or write',
                10000
            )
            ->addOption(
                'port',
                'p',
                InputOption::VALUE_OPTIONAL,
                'server port to connect to',
                5100
            )
            ->addOption(
                'udp',
                'u',
                InputOption::VALUE_NONE,
                'use UDP rather than TCP'
            )
            ->addOption(
                'window',
                'w',
                InputOption::VALUE_REQUIRED,
                'TCP window size (socket buffer size)'
            )
            ->addOption(
                'bind',
                'B',
                InputOption::VALUE_REQUIRED,
                "bind to <host>, an interface or multicast address"
            )
            ->addOption(
                'mss',
                'M',
                InputOption::VALUE_REQUIRED,
                'set TCP maximum segment size'
            )
            ->addOption(
                'nodelay',
                'N',
                InputOption::VALUE_NONE,
                "set TCP no delay, disabling Nagle's Algorithm"
            );
            //->addOption(
            //'num',
            // 'b',
            // InputOption::VALUE_REQUIRED,
            // 'number of bytes to transmit (instead of -t)'
            //)
            //->addOption(
            //'time',
            // 't',
            // InputOption::VALUE_REQUIRED,
            // 'time in seconds to transmit for',
            // 10
            //)
            //->addOption(
            //    'linux-congestion',
            //    'Z',
            //    InputOption::VALUE_REQUIRED,
            //    'set TCP congestion control algorithm (Linux only)'
            //);
    }

    /**
     * Execute the command.
     *
     * @return void
     */
    protected function fire() {
        $this->loopSystemCommand();

        $this->output->writeln('<info>Test finished.</info>');
    }

    /**
     * Nullify properties for new test
     */
    private function setup() {
        $this->matches = [];
        $this->result  = null;
    }

    /**
     * Loop the given command
     */
    private function loopSystemCommand() {
        $this->validate();

        $this->buildCommand();

        foreach(range(1, $this->argument('count')) as $index) {
            $this->setup();

            $this->result = system($this->command, $result);

            $history = $this->persistOutput();

            $history
                ? $this->output->writeln("<info>Test index {$index} done.</info>")
                : $this->output->writeln("<error>Test index {$index} done with error.</error>");

            sleep($this->option('sleep'));
        }
    }

    /**
     * Validate the passed argument and option
     */
    private function validate() {
        $errors = [];

        if (! is_numeric($this->argument('count'))) {
            $errors[] = 'count must be a number. ex) loop 100';
        }

        if ($this->argument('count') > 1000) {
            $errors[] = 'count must be smaller than 1000. ex) loop 999';
        }

        if (preg_match_all('/^(([a-zA-Z0-9-_]+\.[a-zA-Z0-9-_.]+)|localhost)/', $this->option('client')) < 1) {
            $errors[] = 'invalid server hostname. ex) -c localhost';
        }

        if ($this->option('no-reverse')) {
            if (in_array($this->option('client'), ['speedgiga1.airplug.com', 'speedgiga2.airplug.com'])) {
                $errors[] = 'hostname must be given with --no-reverse|-R. ex) -c localhost';
            }
        }

        if (! is_numeric($this->option('sleep'))) {
            $errors[] = 'sleep must be a number. ex) -S 2';
        }

        //if (! $valid = is_numeric($this->option('interval'))) {
        //    $errors[] = ['interval' => 'must be a number'];
        //}

        if (! is_numeric($this->option('len'))) {
            $errors[] = 'len must be a number. ex) -l 1024';
        }

        if (! is_numeric($this->option('port'))) {
            $errors[] = 'port must be a number. ex) -p 5001';
        }

        if ($window = $this->option('window')) {
            if (! is_numeric($window)) {
                $errors[] = 'window must be a number. ex) -w 8';
            }
        }

        if ($bind = $this->option('bind')) {
            if (! filter_var($bind, FILTER_VALIDATE_IP)) {
                $errors[] = 'bind must be a valid ip address. ex) -B 127.0.0.1';
            }
        }

        if ($mss = $this->option('mss')) {
            if (! is_numeric($mss)) {
                $errors[] = 'mss must be a number. ex) -B 128';
            }
        }

        //if ($num = $this->option('num')){
        //    if (! is_numeric($num)) {
        //        $errors[] = 'num must be a number';
        //    }
        //}

        //if (! is_numeric($this->option('time'))) {
        //    $errors[] = 'time must be a number';
        //}

        if ($errors) {
            throw new \InvalidArgumentException(implode("\n", $errors));
        }
    }

    /**
     * build iperf command to run
     */
    private function buildCommand() {
        $command = [];

        $command[] = $this->option('no-reverse') ? 'iperf' : __DIR__.'/../vendor/bin/iperf';
        $command[] = '-c ' . $this->option('client');
        $command[] = '-p ' . $this->option('port');
        //$command[] = '-i ' . $this->option('interval');
        //$command[] = '-t ' . $this->option('time');

        if ($this->option('udp')) {
            $command[] = '-u';
        } else {
            $command[] = '-l ' . $this->option('len');
        }

        if ($this->option('window')) {
            $command[] = '-w ' . $this->option('window');
        }

        if ($this->option('bind')) {
            $command[] = '-B ' . $this->option('bind');
        }

        if ($this->option('mss')) {
            $command[] = '-M ' . $this->option('mss');
        }

        if ($this->option('nodelay')) {
            $command[] = '-N';
        }

        //if ($this->option('linux-congestion')) {
        //    $command[] = '-Z ' . $this->option('linux-congestion');
        //}

        //if ($this->option('num')) {
        //    $command[] = '-n ' . $this->option('num');
        //}

        $this->command = implode(' ', $command);
    }

    /**
     * Parse system() result and save to the database
     *
     * @return History
     */
    private function persistOutput() {
        $path  = __DIR__.'/../database/database.sqlite';

        if (! file_exists($path)) {
            file_put_contents($path, null);
        }

        if (! Capsule::schema()->hasTable('histories')) {
            $this->createTable();
        }

        $count = preg_match_all($this->pattern, $this->result, $this->matches);

        if ($count < 1) {
            return false;
        }

        return History::create([
            'pid'       => getmypid(),
            //'hostname'  => gethostname(),
            //'ip_addr'   => gethostbyname(gethostname()),
            'command'   => stristr($this->command, 'iperf '),
            'result'    => $this->result,
            'speed'     => isset($this->matches['speed']) ? $this->matches['speed'][0] : null,
            'unit'      => isset($this->matches['unit']) ? $this->matches['unit'][0] : null,
            'tested_at' => Carbon::now()->toDateTimeString()
        ]);
    }

    /**
     * create histories table for "History" Eloquent Model
     */
    private function createTable() {
        Capsule::schema()->create('histories', function(Blueprint $table) {
            $table->increments('id');
            $table->integer('pid')->nullable();
            //$table->string('hostname')->nullable();
            //$table->string('ip_addr')->nullable();
            $table->string('command')->nullable();
            $table->string('result')->nullable();
            $table->integer('speed')->nullable();
            $table->string('unit')->nullable();
            $table->timestamp('tested_at');
        });
    }
}