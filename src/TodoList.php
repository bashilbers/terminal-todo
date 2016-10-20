<?php

namespace GTD;

interface TodoList
{
    public function getIdentity();

    public function add(Todo $todo);

    public function finish(Todo $todo);

    public function remove(Todo $todo);

    public function move(Todo $todo, $position);

    public function clear();
}
