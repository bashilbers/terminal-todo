<?php

require_once __DIR__ . '/vendor/autoload.php';

$application = new \GTD\Application();
$application->run();

exit;

global $gtd;
$gtd = new GTD();



// see of we need to print the list id's
if(isset($argv[1]) && $argv[1] == 'wunderlists'){

	$lists = $gtd->wunderlist->getLists();
	foreach($lists as $list){
		w($list->id . ' - ' . $list->title);
	}

	die;
}




