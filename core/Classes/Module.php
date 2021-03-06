<?php

namespace esc\Classes;


class Module
{
    public $name;
    public $class;

    public function __construct(string $name, string $class)
    {
        $this->name = $name;
        $this->class = $class;
    }

    public function load()
    {
        new $this->class();
    }
}