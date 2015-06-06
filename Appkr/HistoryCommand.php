<?php namespace Appkr;

use History;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Database\Capsule\Manager as Capsule;

class HistoryCommand extends Command {

    use CommandTrait, EloquentTrait;

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
        $this->setName('history')
             ->setDescription('Show the test history.')
             ->addArgument(
                 'limit',
                 InputArgument::OPTIONAL,
                 'number of test results to fetch.'//,
                 //1
             )
             ->addOption(
                 'extract',
                 'e',
                 InputOption::VALUE_NONE,
                 'extract and save test history to CSV format.'
             )
            ->addOption(
                 'truncate',
                 't',
                 InputOption::VALUE_NONE,
                 'truncate test history.'
             );
    }

    /**
     * Execute the command.
     *
     * @return void
     */
    protected function fire() {
        $this->validate();

        if ($this->option('truncate')) {
            if ($this->ask('Are you sure you want to delete all the test histories? <info>[y|N]</info>') == 'y') {
                $this->truncate();
            }

            return;
        }

        if ($this->option('extract')) {
            $this->extract();
        } else {
            $this->render();
        }
    }

    /**
     * Validate the passed argument and option
     */
    private function validate() {
        $errors = [];

        if ($this->argument('limit')) {
            if (! is_numeric($this->argument('limit'))) {
                $errors[] = 'limit must be a number. ex) history 100';
            }

            //if ($this->argument('limit') > 1000) {
            //    $errors[] = 'limit must be smaller than 1000. ex) history 999';
            //}
        }

        if ($errors) {
            throw new \InvalidArgumentException(implode("\n", $errors));
        }
    }

    /**
     * Export speed test histories to CSV format
     */
    public function extract() {
        set_time_limit(360);
        $filename = 'iperf-stress-export_' . \Carbon\Carbon::now()->timestamp . '.csv';
        $path = __DIR__ . '/../exports/' . $filename;

        $histories = $this->fetchHistory();

        ob_start();
        $fp = fopen($path, 'w');

        fputcsv($fp, array_keys($histories[0]->toArray()), ',', '"');

        foreach ($histories as $history) {
            fputcsv($fp, $history->toArray(), ',', '"');
        }

        fclose($fp);
        ob_get_clean();

        $this->output->writeln("<info>CSV file created at {$path}</info>");
    }

    /**
     * Render speed test histories to screen
     */
    private function render() {
        $histories = $this->fetchHistory();

        $headers = Capsule::schema()->getColumnListing('histories');

        $this->table($headers, $histories->toArray());
    }

    /**
     * fetch test result history
     *
     * @return mixed
     */
    private function fetchHistory() {
        return ($limit = $this->argument('limit'))
            ? History::orderBy('pid', 'desc')->orderBy('tested_at', 'desc')->limit($limit)->get()
            : History::orderBy('pid', 'desc')->orderBy('tested_at', 'desc')->get();
    }

    /**
     * Truncate History model.
     */
    private function truncate() {
        History::truncate();

        $this->output->writeln("<info>test history is now empty.</info>");
    }

}