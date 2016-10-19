<?php

class Jira{

	private $config;

	public function __construct($config){
		$this->config = $config;
	}

	
	public function registerTime($comment, $key, $timeSpend)
	{
		
		if($timeSpend < 60){
			$timeSpend = 60;
		}

		$worklog = [
			"comment" => $comment, 
			"timeSpentSeconds" => $timeSpend
		];

		$res = $this->post('/rest/api/2/issue/'.$key.'/worklog', $worklog);
		return json_decode($res);
	}

	public function getCommentsForIssue($key){
		$data = $this->get('/rest/api/2/issue/'.$key.'/comment');
		return json_decode($data);
	}

	public function getOutdated(){
		$query = 'Sprint in (openSprints()) AND category = "Development Team" AND Status not in (Closed, Open, "In progress") ORDER BY updated ASC, due ASC, priority DESC, created ASC';
		return $this->query($query);
	}

	function query($jql){

		$data = $this->get('rest/api/2/search?jql=' . urlencode($jql));
		
		return json_decode($data);
	}

	function post($url, $data, $options = []){
		
		$url = $this->config->host . $url;
		$json = json_encode($data);

	
		$defaults = [
			CURLOPT_POST => 1,
			CURLOPT_HEADER => 0,
			CURLOPT_POSTFIELDS => $json,
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/json'
			],
			CURLOPT_USERPWD => $this->config->username . ":" . $this->config->password,
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
	        e('CURL ERR >>' . curl_error($process));
	    }

	    
		curl_close($process);

		return $response;
	}


	function get($url){

	
		$url = $this->config->host . $url;

		$defaults = [
			CURLOPT_HEADER => 0,
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/json'
			],
			CURLOPT_USERPWD => $this->config->username . ":" . $this->config->password,
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
	        e('CURL ERR >>' . curl_error($process));
	    }

		curl_close($process);

		return $response;
	}

}