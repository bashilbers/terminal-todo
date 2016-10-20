<?php

namespace GTD;

use GTD\Command\ConfigureCommand;
use GTD\TodoList\Wunderlist;
use Symfony\Component\Console\Application as Base;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends Base
{
    public $jira;
    public $wunderlist;
    public $config;
    public $mainConfig;
    public $harvest;

    private $debugging = false;
    private $tracktime = true;

    /**
     * @var Workspace[]
     */
    protected $workspaces = [];

    /**
     * Gets the default commands that should always be available.
     *
     * @return array An array of default Command instances
     */
    protected function getDefaultCommands()
    {
        // Keep the core default commands to have the HelpCommand
        // which is used when using the --help option
        $defaultCommands = parent::getDefaultCommands();

        $runCommand = new Command('start');
        $runCommand->setCode([$this, 'start']);

        $syncCommand = new Command('sync');
        $syncCommand->setCode([$this, 'sync']);

        $defaultCommands[] = $runCommand;
        $defaultCommands[] = $syncCommand;
        $defaultCommands[] = new ConfigureCommand();

        return $defaultCommands;
    }

    public function getDefaultInputDefinition()
    {
        $definition = parent::getDefaultInputDefinition();

        $definition->addOption(new InputOption(
            'configuration',
            'c',
            InputOption::VALUE_REQUIRED,
            'Configuration file',
            !empty(getEnv('gtd-config')) ? getenv('gtd-config') : null
        ));

        return $definition;
    }

    public function start(InputInterface $input, OutputInterface $output)
    {
        $output->getFormatter()->setStyle('w', new OutputFormatterStyle('green'));

        $file = $input->getOption('configuration');

        if (!file_exists($file)) {
            throw new \LogicException(sprintf('Config file "%s" does not exist', $file));
        }

        $config = json_decode(file_get_contents($file), true);

        $workspaces = [];
        foreach ($config['workspaces'] as $name => $element) {
            $workspace = new Workspace($name, TimeFrame::parse($element['timeframe']));

            foreach ($element['projects'] as $project) {
                switch ($project['type']) {
                    case 'wunderlist':
                        $workspace->addTodoList(new Wunderlist(
                            $this->getProvider($project['type']),
                            $project['id']
                        ), $project['priority']);
                        break;
                }
            }

            $workspaces[] = $workspace;
        }

        $this->workspaces = $workspaces;

        do {
            $this->setActiveWorkspace();
            $todo = $this->next();
            $this->handle($todo);
        } while ($todo instanceof Todo);

        $todo = true;
        while($todo){
            $todo = $this->next();

            if(!$todo){
                w('Go home!');
                w('Nothing to do!');
                break;
            }

            $this->startTodo($todo);

        }

        $output->writeln($config);
        return;

        getenv('foo');

        // configuratie laden
        $configJson = file_get_contents(__DIR__ . '/config.json');
        $this->mainConfig = json_decode($configJson);

        foreach($this->mainConfig->workspaces as $name => $config){
            // check if the workspace is active
            if(!$this->workspaceIsActive()) {
                $this->debug('Workspace not for this day of week');
                continue;
            }

            $this->setWorkspace($config);
        }

        if(!isset($this->config)){
            e('Nothing to do right now. Go play!');
            return;
        }
    }

    public function getProvider($type, array $config)
    {
        switch ($type) {
            case 'wunderlist':
                //return new \GTD\TodoListProvider\Wunderlist(

                //)
                break;
        }
    }

    // function for starting the todo
    function startTodo($todo){
        system('clear');

        $todoStart = time();

        w('====================================');
        w($todo->project->name);
        w('====================================');

        w('');
        w($todo->title);
        w('------------------------------------');

        if(isset($todo->notes) && is_array($todo->notes)){
            foreach($todo->notes as $note){
                w($note->content);
                w('------------------------------------');
            }
        }

        w('');
        w('');
        w('Timer started at: ' . date('H:i', $todoStart));
        w('');
        w('done 	> To-do is done');
        w('stop 	> Stop working on the to-do');
        w('bump 	> Bump to the bottom of the list');
        w('adhoc 	> Do something ad hoc');
        w('exit 	> Exit the program');
        w('');

        $next = r('>     ', 'stop');
        $timeSpend = time() - $todoStart;


        if($next == 'done'){
            $this->done($todo);
            $this->clockTime($todo, $timeSpend);
        }elseif (substr($next, 0,5) == 'adhoc') {
            $title = substr($next, 6);
            $todo = $this->adhoc($title);
            $this->startTodo($todo);
        }elseif ($next == 'bump') {
            $this->bump($todo);
        }elseif ($next == 'stop') {
            $this->clockTime($todo, $timeSpend);
            die;
        }elseif($next == 'exit'){
            e('Exiting.... No time clocked!');
        }
    }

    private function debug($s, $context = [])
    {

        if(count($context) > 0 && is_array($context)){
            $keys = array_keys($context);
            $key = $keys[0];
            $s .=  ' ' . $context[$key];
        }

        print $s;

        if($this->debugging){


            if($context !== []){
                print ' - ' . json_encode($context);
            }

        }

        print PHP_EOL;

    }

    private function setActiveWorkspace()
    {
        // filter out any workspaces that are not active
        $workspaces = array_filter($this->workspaces, function (Workspace $workspace) {
           return $workspace->isActive();
        });

        if (count($workspaces) === 1) {
            $this->activeWorkspace = $workspaces[0];
            return;
        }

        if (count($workspaces) === 0) {
            $this->output->writeLn('<danger>No active workspaces for now</danger>');
            return;
        }

        // let user select one




        // jira starten
        if(isset($this->config->jira)){
            $this->debug('Initializing Jira!');
            // TODO Move to config
            $this->config->jira->keypattern = "/(API|BBMODULE|BB|DEVTEAM|ADAPTERS|DCK|RET)\-([0-9]+)/";
            $this->jira = new Jira($this->config->jira);
        }

        // wunderlist starten
        $this->wunderlist = new Wunderlist($this->config->wunderlist);

        // harvest starten
        $this->harvest = new Harvest($this->config->harvest);
    }

    public function next()
    {
        $this->debug('Getting next todo');

        // eerstvolgende todo ophalen
        foreach($this->config->projects as $project) {
            $this->debug('Reading wunderlist', $project);

            $todos = $this->wunderlist->getListTodos($project->wunderlist_id);

            if(count($todos) == 0){
                $this->debug('No todo\'s found in list for project', $project);
                continue;
            }

            $todosOrdered = [];
            foreach($todos as $todo){

                // check if it is due today
                if(strtotime($todo->due_date) > time()){
                    $this->debug('Todo is in the future', $todo);
                    continue;
                }
                $index = strtotime($todo->created_at) . $todo->revision;
                $todosOrdered[$index] = $todo;
            }

            if(count($todosOrdered) == 0){
                $this->debug('No todo\'s found in list for today', $project);
                continue;
            }

            // get the full todo and it's description
            $firstToDo = array_values($todosOrdered)[0];
            $firstToDo->notes = $this->wunderlist->getNotes($firstToDo->id);
            $firstToDo->project = $project;


            // Create jira links for the tickets
            if(isset($this->config->jira)){
                $matches = [];
                if(preg_match($this->config->jira->keypattern, $firstToDo->title, $matches)){
                    $url = $this->config->jira->host . 'browse/' . $matches[0];
                    $firstToDo->notes[] = (object) [
                            'content' => $url];
                }


                if(!empty($firstToDo->project->jira_key)){
                    $url =  $this->config->jira->host . 'browse/' . $firstToDo->project->jira_key;
                    $firstToDo->notes[] = (object) [
                            'content' => $url
                        ];
                }
            }

            // harvest timer starten
            return $firstToDo;
        }

    }

    public function clockTime($todo, $timeSpend)
    {
        $this->debug('Clocking time for todo', $todo);

        // TODO SET IN CONFIG
        $minimum = 5 * 60;

        if($timeSpend < $minimum){
            $timeSpend = $minimum;
        }else{
            $extra = ($timeSpend % $minimum);
            $extra = $minimum - $extra;
            $timeSpend += $extra;
        }


        if(!$this->tracktime){
            $this->debug('Time tracking disabled.');
            return;
        }

        // tijd registreren
        if(!empty($todo->project->harvest_id)){
            $this->debug('Clocking to harvest', $timeSpend);
            $this->harvest->registerTime($todo->title, $todo->project->harvest_id, $timeSpend);
        }

        if(isset($this->config->jira)){

            // register any time for work
            if(!empty($todo->project->jira_key)){
                $this->debug('Clocking to JIRA', [
                        'key' => $todo->project->jira_key,
                        'time' => $timeSpend
                    ]);
            }else{

                // try to find the issue number via a regex
                $matches = [];
                $pattern = $this->config->jira->keypattern;

                if(preg_match($pattern, $todo->title, $matches)){
                    $jiraKey = $matches[0];
                    $res = $this->jira->registerTime($todo->title, $jiraKey, $timeSpend);
                    $this->debug('Clocking to JIRA', [
                        'key' => $jiraKey,
                        'time'=>$timeSpend,
                        'res' => $res
                    ]);
                }
            }
        }
    }


    public function done($todo)
    {

        // todo afvinken
        $this->debug('Mark todo as done', $todo);
        $this->wunderlist->done($todo);
    }


    public function adhoc($title)
    {
        $this->debug('Creating ad hoc task',[
            'title' => $title
        ]);


        $keys = array_keys($this->config->projects);
        $list = $this->config->projects[$keys[0]];

        $todo = new stdClass;
        $todo->list_id = $list->wunderlist_id;
        $todo->title = $title;


        return $this->wunderlist->createTodo($todo);
    }


    public function bump($todo)
    {
        $this->debug('Bumping to-do', $todo);

        if(isset($todo->recurrence_count) && $todo->recurrence_count > 0){
            e('Can\'t bump recurring tasks');
        }

        // Copy the todo
        $this->wunderlist->copy($todo);

        $this->done($todo);
    }

    public function sync()
    {
        $this->debug('Getting outdated tickets and add them to Wunderlist');

        // Get outdated tickets
        $outdatedTickets = $this->jira->getOutdated();

        $ticketsToPush = [];
        foreach($outdatedTickets->issues as $i=>$issue){

            $this->debug('Getting last action for issue ' . $issue->key, $issue);
            $ticketsToPush[$issue->key] = [
                'title' => $issue->fields->summary,
                'updated' => strtotime($issue->fields->updated)
            ];


            // get the last comment
            $comments = $this->jira->getCommentsForIssue($issue->key);
            $commentTimestamps = [];
            foreach($comments->comments as $comment){
                $timestamp = strtotime($comment->updated);
                $commentTimestamps[] = $timestamp;
            }

            sort($commentTimestamps);
            if(count($commentTimestamps) > 0){
                $timestamp = $commentTimestamps[0];
                if(strtotime($issue->fields->updated) < $timestamp){
                    $ticketsToPush[$issue->key]['last_comment'] = $timestamp;
                }

            }else{
            }

            $this->debug('Using updated at as timestamp');
            $timestamp = strtotime($issue->fields->updated);
            $ticketsToPush[$issue->key] = $timestamp;
        }


        $keys = array_keys($this->config->projects);
        $list = $this->config->projects[$keys[0]];

        $threshold = strtotime('NOW - 2 DAYS 4 HOURS');
        foreach($ticketsToPush as $issueKey => $issue){

            $timestamp = $issue['updated'];
            if(isset($issue['last_comment']) && $issue['last_comment'] > $issue['updated']){
                $this->debug('Using last comment as date');
                $timestamp = $issue['last_commment'];
            }

            if( $timestamp < $threshold){

                $todo = new stdClass;
                $todo->list_id = $list->wunderlist_id;
                $todo->title = 'Check ' . $issueKey;
                $todo->due_date = date('Y-m-d');

                $this->debug('Creating Wunderlist todo for outdated issue ' . $issueKey);
            //	$this->wunderlist->createTodo($todo);
            }


        }


    }

}