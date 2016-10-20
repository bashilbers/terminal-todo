<?php

namespace GTD;

use Symfony\Component\Stopwatch\Stopwatch;

class Todo
{
    protected $title;

    protected $project;

    /** @var Note[] */
    protected $notes;

    /**
     * @var Stopwatch
     */
    protected $stopwatch;

    public function __construct($title, $project, array $notes = [])
    {
        $this->title = $title;
        $this->project = $project;
        $this->notes = $notes;
        $this->stopwatch = new Stopwatch();
    }

    public function start()
    {
        $this->stopwatch->start('work');

        return $this;
    }

    public function finish()
    {
        $this->stopwatch->stop('work');

        return $this->stopwatch;
    }
}
