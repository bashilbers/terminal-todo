<?php

namespace GTD;

class Workspace
{
    protected $name;

    protected $timeframe;

    /**
     * @var \SplPriorityQueue
     */
    protected $todoLists;

    public function __construct($name, Timeframe $window)
    {
        $this->name = $name;
        $this->timeframe = $window;
        $this->todoLists = new \SplPriorityQueue();
    }

    public function isActive()
    {
        return $this->timeframe->isDue();
    }

    public function addTodoList(TodoList $list, $priority)
    {
        $this->todoLists->insert($list, $priority);
    }

    public function getTodoLists()
    {
        return $this->todoLists;
    }
}
