<?php

namespace GTD\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class ConfigureCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('configure')
            ->setDescription('Creates a basic config.json file in current directory.')
            ->setDefinition([
                new InputOption('name', null, InputOption::VALUE_REQUIRED, 'Name of the package')
            ])
            ->setHelp(<<<EOT
The <info>configure</info> command creates a basic config.json file
in the current directory.
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $projects = [];

        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');
        $confirmQuestion = new ConfirmationQuestion("Would you like to add a workspace? [<comment>yes</comment>] ", true);

        while ($questionHelper->ask($input, $output, $confirmQuestion)) {
            $nameQuestion = new Question('Enter a workspace name (e.g. <info>Work</info> or <info>Side projects</info>): ');
            $nameQuestion->setValidator(function ($val) use ($output) {
                if (empty($val)) {
                    $output->writeln('<error>name is empty, please try again</error>');
                    return null;
                }
                return $val;
            });

            $name = $questionHelper->ask($input, $output, $nameQuestion);

            if (empty($name)) {
                continue;
            }

            $projects[$name] = [
                'timeframe'    => [],
                'projects'     => [],
                'integrations' => []
            ];

            $askDays = new ConfirmationQuestion(
                sprintf("Does <info>%s</info> only affect certain days? [<comment>yes</comment>]", $name),
                true
            );
            if ($questionHelper->ask($input, $output, $askDays)) {
                $days = [
                    0 => 'Monday',
                    1 => 'Tuesday',
                    2 => 'Wednesday',
                    3 => 'Thursday',
                    4 => 'Friday',
                    5 => 'Saturday',
                    6 => 'Sunday'
                ];

                $daysQuestion = new ChoiceQuestion(
                    'Please select the days (use comma to select multiple)',
                    $days,
                    '0,1,2,3,4,5,6'
                );

                $daysQuestion->setMultiselect(true);

                $days = $questionHelper->ask($input, $output, $daysQuestion);
                $output->writeln('You have just selected: ' . implode(', ', $days));

                $projects[$name]['timeframe']['days'] = array_keys($days);
            }

            $askStartAndEndTime = new ConfirmationQuestion(
                sprintf("Does <info>%s</info> only affect certain times? (e.g. <info>9:00</info> till <info>17:00</info>)? [<comment>yes</comment>]", $name),
                true
            );
            if ($questionHelper->ask($input, $output, $askStartAndEndTime)) {
                $hours = range(1, 24);
                $hours = array_combine($hours, $hours);
                $startHourQuestion = new ChoiceQuestion(
                    'Please enter starting hour',
                    $hours
                );

                $startHour = $questionHelper->ask($input, $output, $startHourQuestion);
                $output->writeln('You have just selected: ' . $startHour);

                $hours = range(1, 24);
                $hours = array_combine($hours, $hours);
                $endHourQuestion = new ChoiceQuestion(
                    'Please enter ending hour',
                    $hours
                );

                $endHour = $questionHelper->ask($input, $output, $endHourQuestion);
                $output->writeln('You have just selected: ' . $endHour);

                $projects[$name]['timeframe']['start'] = $startHour;
                $projects[$name]['timeframe']['end'] = $endHour;
            }

            $confirmQuestion = new ConfirmationQuestion("Would you like to add another workspace? [<comment>yes</comment>] ", true);
        }

        var_dump($projects);
        exit;
    }
}
