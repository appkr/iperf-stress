<?php namespace Appkr;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait CommandTrait {

    /**
     * Execute the command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface   $input
     * @param  \Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output) {
        $this->input = $input;
        $this->output = $output;

        return $this->fire();
    }

    /**
     * Get an argument from the input.
     *
     * @param  string $key
     * @return string
     */
    public function argument($key) {
        return $this->input->getArgument($key);
    }

    /**
     * Get an option from the input.
     *
     * @param  string $key
     * @return string
     */
    public function option($key) {
        return $this->input->getOption($key);
    }

    /**
     * Ask the user the given question.
     *
     * @param  string $question
     * @return string
     */
    public function ask($question) {
        $question = '<comment>' . $question . '</comment> ';

        return $this->getHelperSet()->get('dialog')->ask($this->output, $question);
    }

    /**
     * Ask the user the given secret question.
     *
     * @param  string $question
     * @return string
     */
    public function secret($question) {
        $question = '<comment>' . $question . '</comment> ';

        return $this->getHelperSet()->get('dialog')->askHiddenResponse($this->output, $question, false);
    }

    /**
     * Format input to textual table
     *
     * @param  array  $headers
     * @param  array  $rows
     * @param  string $style
     * @return void
     */
    public function table(array $headers, array $rows, $style = 'default') {

        $table = new Table($this->output);

        $table->setHeaders($headers)->setRows($rows)->setStyle($style)->render();
    }

}
