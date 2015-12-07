<?php

namespace Appkr;

use Carbon\Carbon;
use History;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class LoopCommand extends Command
{
    use CommandTrait;
    use EloquentTrait;

    /**
     * System command to run
     *
     * @var string
     */
    protected $command = null;

    /**
     * Regex pattern for $this->result parsing
     *
     * @var string
     */
    protected $pattern = '/^\[\s*\d*\]\s*[0-9-.]+\s*(sec)\s*(?P<data>[\d.]+)\s*(GBytes|MBytes|KBytes)\s*(?P<speed>[\d.]+)\s*(?P<unit>(Gbits\/sec|Mbits\/sec|Kbits\/sec))/';

    /**
     * iperf result string
     *
     * @var string
     */
    protected $result = null;

    /**
     * Regex result of $this->result
     *
     * @var array
     */
    protected $matches = [];

    public function __construct()
    {
        $this->bootDatabase();
        $this->checkDatabase();

        parent::__construct();
    }

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('loop')
            ->setDescription('Looping through the given tests.')
            ->addArgument(
                'count',
                InputArgument::OPTIONAL,
                'number of loop. acceptable maximum value is 1000',
                1
            )
            ->addOption(
                'sleep',
                's',
                InputOption::VALUE_OPTIONAL,
                'sleep between loops in sec',
                1
            )
            ->addOption(
                'reverse',
                'r',
                InputOption::VALUE_NONE,
                "if provided, up/down swap patched 'novak/iperf' is used."
            )
            ->addOption(
                'client',
                'c',
                InputOption::VALUE_REQUIRED,
                "run in client mode, connecting to the given <host>",
                'localhost'
            )
            ->addOption(
                'len',
                'l',
                InputOption::VALUE_OPTIONAL,
                'length of buffer to read or write. (only applicable to tcp.)',
                10000
            )
            ->addOption(
                'port',
                'p',
                InputOption::VALUE_OPTIONAL,
                'server port to connect to',
                5001
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
                'mss',
                'm',
                InputOption::VALUE_REQUIRED,
                'set TCP maximum segment size'
            )
            ->addOption(
                'nodelay',
                'N',
                InputOption::VALUE_NONE,
                "set TCP no delay, disabling Nagle's Algorithm"
            );
    }

    /**
     * Execute the command.
     *
     * @return mixed
     */
    protected function fire()
    {
        // Validate user provided argument and option values.
        $this->validate();

        // Build iperf system command, based on the user inputs.
        $this->buildCommand();

        // Delegate job.
        $this->loopSystemCommand($this->argument('count'));

        // Notify the result of execution.
        return $this->output->writeln('<info>Test finished.</info>');
    }

    /**
     * Nullify properties for next loop.
     */
    protected function prepare()
    {
        $this->matches = [];
        $this->result = null;
    }

    /**
     * Loop the given command.
     *
     * @param integer
     */
    protected function loopSystemCommand($count)
    {
        foreach (range(1, $count) as $index) {
            $this->prepare();

            $this->result = system($this->command, $result);

            $history = $this->persistOutput();

            $history
                ? $this->output->writeln("<info>Test index {$index} done.</info>")
                : $this->output->writeln("<error>Test index {$index} done with error.</error>");

            sleep($this->option('sleep'));
        }

        return;
    }

    /**
     * build iperf command to run.
     */
    protected function buildCommand()
    {
        $command = [];
        $command[] = $this->option('reverse')
            ? __DIR__ . '/../vendor/appkr/iperf/src/iperf'
            : 'iperf';
        $command[] = sprintf('-c %s', $this->option('client'));
        $command[] = sprintf('-p %d', $this->option('port'));
        $command[] = $this->option('udp')
            ? '-u'
            : sprintf('-l %d', $this->option('len'));

        if ($w = $this->option('window')) {
            $command[] = '-w ' . $w;
        }

        if ($m = $this->option('mss')) {
            $command[] = '-M ' . $m;
        }

        if ($n = $this->option('nodelay')) {
            $command[] = '-N';
        }

        $this->command = implode(' ', $command);

        return;
    }

    /**
     * Parse system() result and save to the database
     *
     * @return History
     */
    protected function persistOutput()
    {
        $count = preg_match_all($this->pattern, $this->result, $this->matches);

        if ($count < 1) {
            return false;
        }

        return History::create([
            'pid'       => getmypid(),
            'command'   => trim(stristr($this->command, 'iperf ')),
            'result'    => $this->result,
            'speed'     => isset($this->matches['speed']) ? $this->matches['speed'][0] : null,
            'unit'      => isset($this->matches['unit']) ? $this->matches['unit'][0] : null,
            'tested_at' => Carbon::now()->toDateTimeString(),
        ]);
    }
}