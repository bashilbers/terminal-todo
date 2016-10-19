# Terminal todo / GTD focus tool

I've promised my collegues that I'd open source this tool. This tool helps me focus on my work. It reads my tasks from Wunderlist, depending on task type/priority, the time of day and the day of the week. 

# Configuration
This project uses a configuration file in JSON format. This file should be placed in the root of the project and should be named `config.json`. See `config.sample.json` for an example 

## Workspaces
This tool works with different "workspaces". I've got a workspace for my side projects and one for my dayjob. My dayjob workspace is configured to be only called on monday to friday. This is done by the parameter "days" in the configuration of the workspace. This should be an array of numbers. The numbers should correspondent with the day of week from PHP's date function. 

You can also state the time a workspace should start being acive. You must set a "start" and "end" for each workspace. These are the hours of the day. So if you'd start at 09:00 in the morning and stop at 17:00 on your dayjob, you can enter 9 as start and 16 as end. 

## Wunderlist
You must configure wunderlist, otherwise this app won't work. You need to register your app at wunderlist, so you can get API access for yourself. You've got login at the [Wunderlist Developer platform](https://developer.wunderlist.com/apps/new) and register your app. Once you've got the

## Projects 
Projects are basically lists in wunderlist that get's read by this tool. The wunderlist_id is the ID of the list in wunderlist. For more information about the wunderlist lists, see the [Wunderlist API documentation](https://developer.wunderlist.com/documentation/endpoints/list). 

pro-tip: You can easily fetch all your wunderlist list id's by executing: `php console.php wunderlists`
 
## Harvest
Harvest is being used for timetracking and can be configured by providing the subdomain, username, password and a default task_id. 

## JIRA
Jira is also used for timetracking. 

## Global command in mac to start the tool
I've registered the "todo" command in my `~/.bash_profile` as a function. You could add this line to make this globally available in your terminal: 
`function todo() { php full-path-to-the-project/console.php ;}`