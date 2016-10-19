<?php

class Wunderlist{

	private $config;

	public function __construct($config){
		$this->config = $config;

		$this->headers = [
		    "X-Access-Token: " . $this->config->token,
		    "X-Client-ID: " . $this->config->id,
		    'Content-Type: application/json'
		];

	}

	public function done($todo){
		$payload = json_encode([
				'revision' => $todo->revision,
				'completed' => true
			]);
		curl_request('PATCH', 'a.wunderlist.com/api/v1/tasks/' . $todo->id, $this->headers,[
			CURLOPT_POSTFIELDS => $payload
		]);

	}

	public function addNote($taskId, $content){
		
		$payload = json_encode([
			'task_id' => $taskId,
			'content' => $content
		]);

		$result = curl_request('POST', 'a.wunderlist.com/api/v1/notes', $this->headers,[
			CURLOPT_POSTFIELDS => $payload
		]);

		return json_decode($result);
	}

	public function copy($todo)
	{
		$copy = $this->createTodo($todo);
		foreach($this->getNotes($todo->id) as $note)
		{
			$this->addNote($todo->id, $note);
		}

		return $copy;
	}

	public function createTodo($todo)
	{

		$newTodo = [];
		$params = ['list_id','title','due_date','recurrence_type'];
		foreach($params as $p){
			
			if(isset($todo->{$p}))
			{
				$newTodo[$p] = $todo->{$p};
			}
		}

		$payload = json_encode($todo);
		$result = curl_request('POST', 'a.wunderlist.com/api/v1/tasks', $this->headers,[
			CURLOPT_POSTFIELDS => $payload
		]);

		return json_decode($result);
	}

	private function get($url){
		$data = curl_request('GET', $url,  $this->headers);
		return json_decode($data);
	}

	public function getListTodos($id){
		return $this->get('a.wunderlist.com/api/v1/tasks?list_id=' . $id);
	}

	public function getNotes($id){
		return $this->get('a.wunderlist.com/api/v1/notes?task_id=' . $id);
	}

	public function getTodo($id)
	{
		return $this->get('a.wunderlist.com/api/v1/tasks/' . $id);
	}

	public function getLists(){
		$url = 'a.wunderlist.com/api/v1/lists';
		return $this->get($url);
	}

	

}