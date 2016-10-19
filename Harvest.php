<?php

class Harvest{

	private $config;

	public function __construct($config){
		$this->config = $config;
		$this->config->auth = base64_encode($this->config->username . ':' . $this->config->password);
	}


	public function registerTime($worklog, $harvest_id, $timeSpend)
	{
		$minutesSpend = $timeSpend / 60;
		$hoursSpend = round($minutesSpend / 60,2);

		if($hoursSpend == 0){
			$hoursSpend = 0.01;
		}

        // just a sample below naturally you need to replace this with the right project and taks ids, as you cannot access these.
        $createXml = "<request> 
        	<notes>".$worklog."</notes> 
        	<hours>".$hoursSpend."</hours> 
        	<project_id>". $harvest_id ."</project_id> 
        	<task_id>". $this->config->task_id . "</task_id> 
        	<spent_at>".date('Y-m-d')."</spent_at> 
        	</request>";


        $url = "https://stefw.harvestapp.com/daily/add";

		$createXml = str_replace(['&'], ['and'], $createXml);
		$c = simplexml_load_string($createXml);

		$headers = [

			CURLOPT_POST => 1,
			CURLOPT_POSTFIELDS => $createXml,
			CURLOPT_HTTPHEADER => [
				"Authorization: Basic " . $this->config->auth,
				"Content-type: application/xml",
		    	"Accept: application/xml",
		    	"Cache-Control: no-cache"
		    ]
		];
		
		$this->curlRequest($url, $headers);
	}

	function curlRequest($url, $options = []){

		$defaults = [
			// CURLOPT_VERBOSE => 1,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_USERAGENT => "StefW Jira Sync",
			CURLOPT_FOLLOWLOCATION => 1,
			CURLOPT_COOKIESESSION => true,
			CURLOPT_COOKIEJAR => '~/StefW/cookiejar.txt',
			CURLOPT_COOKIEFILE => '~/StefW/coockiefile.txt'
		];

		// 
		foreach($defaults as $option=>$default){
			if(!isset($options[$option])){
				$options[$option] = $default;
			}
		}

		$process = curl_init($url);

		foreach($options as $option => $value){
			curl_setopt($process, $option, $value);
		}

		$response = curl_exec($process);

		$err = curl_error($process);
		if($err){
			e($err);
		}


	    if (curl_errno($process)) {
	    	var_dump(curl_getinfo($process));
	        e('CURL ERR >> ' . curl_error($process));
	    }

	    
		curl_close($process);

		return $response;
	}

}