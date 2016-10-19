<?php


include('/Users/stefwijnberg/StefW/scripts/cli.php');
include('Jira.php');
include('Harvest.php');
include('Wunderlist.php');
include('GTD.php');


$gtd = new GTD();

$gtd->sync();