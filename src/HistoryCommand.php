<?php

namespace Appkr;

use Carbon\Carbon;
use League\Csv\Writer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class HistoryCommand extends Command
{
    use CommandTrait;
    use EloquentTrait;

    public function __construct() {
        $this->bootDatabase();

        parent::__construct();
    }

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure() {
        $this->setName('history')
             ->setDescription('Show the test history.')
             ->addArgument(
                 'limit',
                 InputArgument::OPTIONAL,
                 'number of test results to fetch.',
                 null
             )
             ->addOption(
                 'extract',
                 'e',
                 InputOption::VALUE_NONE,
                 'extract and save test history to a CSV file.'
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
        // Validate user provided argument and option values.
        $this->validate();

        // Delegate "truncate" job, and early return.
        if ($this->option('truncate')) {
            if ($this->io->ask('Are you sure you want to delete all the test histories? <info>[y|N]</info>', 'N') == 'y') {
                $this->truncate();
            }

            return;
        }

        $limit = $this->argument('limit');

        // Delegate "extract" job.
        if ($this->option('extract')) {
            $this->extract($limit);

            return;
        }

        // Delegate "render" job.
        $this->render($limit);
    }

    /**
     * Export speed test histories to CSV format.
     *
     * @param int|null $limit
     */
    public function extract($limit = null) {
        // for Mac computer
        // @see https://github.com/thephpleague/csv#configuration
        if (!ini_get("auto_detect_line_endings")) {
            ini_set("auto_detect_line_endings", '1');
        }

        $path = sprintf(
            '%s/./iperf-stress-%d.csv',
            getenv('HOME'),
            Carbon::now()->timestamp
        );

        $writer = $this->writer($path);
        $collection = $this->fetchHistory($limit);

        // Header line
        $writer->insertOne(
            array_keys($collection[0]->toArray())
        );

        // Content
        $writer->insertAll(
            $collection->toArray()
        );

        $this->io->success("CSV file created at {$path}");

        return;
    }

    /**
     * Instantiate the writer instance.
     *
     * @param string $path
     * @return \League\Csv\Writer
     */
    protected function writer($path)
    {
        return Writer::createFromPath(
            new \SplFileObject($path, 'a+'),
            'w'
        );
    }

    /**
     * Render speed test histories to screen
     *
     * @param int|null $limit
     */
    protected function render($limit = null) {
        $collection = $this->fetchHistory($limit);

        $headers = $this->getColumnListings();

        $this->io->table($headers, $collection->toArray());

        return;
    }

    /**
     * Fetch test history.
     *
     * @param null $limit
     * @return mixed
     */
    protected function fetchHistory($limit = null) {
        $query = History::orderBy('pid', 'desc')->orderBy('tested_at', 'desc');

        return $limit
            ? $query->limit($limit)->get()
            : $query->get();
    }

    /**
     * Truncate History model.
     */
    protected function truncate() {
        History::truncate();

        $this->io->success("test history is now empty.");

        return;
    }
}