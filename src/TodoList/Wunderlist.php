<?php

namespace GTD\TodoList;

use GTD\Todo;
use GTD\TodoList;

class Wunderlist implements TodoList
{
    protected $provider;

    protected $id;

    public function __construct(\GTD\TodoListProvider\Wunderlist $provider, $id)
    {
        $this->provider = $provider;
        $this->id = $id;
    }

    public function getIdentity()
    {
        return $this->id;
    }

    public function add(Todo $todo)
    {

    }

    public function finish(Todo $todo)
    {
        $this->provider->done($todo);
    }

    public function remove(Todo $todo)
    {

    }

    public function move(Todo $todo, $position)
    {

    }

    public function clear()
    {

    }
}
