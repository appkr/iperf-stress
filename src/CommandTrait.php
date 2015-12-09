<?php

namespace Appkr;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

trait CommandTrait
{
    public $io;

    public $input;

    public $output;

    /**
     * Execute the command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface   $input
     * @param  \Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        // Available styles. (symfony/console ~2.8 feature)
        // title, section, text, comment, note, caution, listing, table,
        // ask, askHidden, confirm, choice, success, error, warning
        $this->io = new SymfonyStyle($input, $output);

        $this->input = $input;
        $this->output = $output;

        return $this->fire();
    }

    /**
     * Get all arguments.
     *
     * @return array
     */
    public function arguments()
    {
        return $this->input->getArguments();
    }

    /**
     * Get an argument from the input.
     *
     * @param  string $key
     * @return string
     */
    public function argument($key)
    {
        return $this->input->getArgument($key);
    }

    /**
     * Get all options.
     *
     * @return array
     */
    public function options()
    {
        return $this->input->getOptions();
    }

    /**
     * Get an option from the input.
     *
     * @param  string $key
     * @return string
     */
    public function option($key)
    {
        return $this->input->getOption($key);
    }

    /**
     * Do the validation job.
     *
     * @return bool
     * @throw \InvalidArgumentException
     */
    public function validate() {
        $v = History::getValidator(array_merge(
            $this->arguments(),
            $this->options()
        ));

        if ($v->fails()) {
            throw new \InvalidArgumentException(
                $v->errors()->first()
            );
        }

        return true;
    }
}
