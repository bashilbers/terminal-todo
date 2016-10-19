<?php

class GTD{

	public $jira;
	public $wunderlist;
	public $config;
	public $mainConfig;
	public $harvest;

	private $debugging = false;
	private $tracktime = true;

	public function __construct()
	{
		// configuratie laden 
		$configJson = file_get_contents(__DIR__ . '/config.json');
		$this->mainConfig = json_decode($configJson);

		foreach($this->mainConfig->workspaces as $name => $config){

			// check if the workspace is active
			if(!$this->workspaceIsActive($config)){
				continue;
			}

			$this->setWorkspace($config);
			
		}	

		if(!isset($this->config)){
			e('Nothing to do right now. Go play!');
			return;
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

	private function setWorkspace($config){

		$this->debug('Set workplace', $config);
		$this->config = $config;
		

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

	public function workspaceIsActive($config){

		// check if it's the right day
		$dayOfWeek = intval(date('N'));

		if(!in_array($dayOfWeek, $config->days)){
			$this->debug('Workspace not for this day of week', [$name,$config->days, $dayOfWeek]);
			return false;
		}

		// check if it's the right timeframe
		$hourOfDay = intval(date('H'));
		$now = $hourOfDay >= $config->start && $hourOfDay <= $config->end;
		if(!$now){
			$this->debug('Workspace not for this time of day', [$name, $config->start, $config->end]);
			return false;
		}

		return true;

	}


	public function next()
	{

		$this->debug('Getting next todo');

		
		// eerstvolgende todo ophalen
		foreach($this->config->projects as $project){
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
		$this->debug('Mark todo as done', $otod);
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