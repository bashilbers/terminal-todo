<?php

include('cli.php');
include('Jira.php');
include('Harvest.php');
include('Wunderlist.php');
include('GTD.php');


global $gtd;
$gtd = new GTD();

// function for starting the todo
function startTodo($todo){
	system('clear');

	global $gtd;

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
		$gtd->done($todo);
		$gtd->clockTime($todo, $timeSpend);
	}elseif (substr($next, 0,5) == 'adhoc') {
		$title = substr($next, 6);
		$todo = $gtd->adhoc($title);
		startTodo($todo);
	}elseif ($next == 'bump') {
		$gtd->bump($todo);
	}elseif ($next == 'stop') {
		$gtd->clockTime($todo, $timeSpend);
		die;
	}elseif($next == 'exit'){
		e('Exiting.... No time clocked!');
	}
}


// see of we need to print the list id's
if(isset($argv[1]) && $argv[1] == 'wunderlists'){

	$lists = $gtd->wunderlist->getLists();
	foreach($lists as $list){
		w($list->id . ' - ' . $list->title);
	}

	die;
}



$todo = true;
while($todo){

	$todo = $gtd->next();

	if(!$todo){
		w('Go home!');
		w('Nothing to do!');
		break;
	}

	startTodo($todo);

}
