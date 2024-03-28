<?php

namespace Nadi\WordPress\Exceptions;

use Exception;

class WordPressException extends Exception
{
    public string $file;

    public int $line;

    public $data;

    public $class;

    public $traces;

    public function __construct(array $traces, string $message, string $file, string $line, int $code = 0, mixed $data = null, ?string $class = null)
    {
        parent::__construct($message, $code);
        $this->file = $file;
        $this->line = $line;
        $this->data = $data;
        $this->class = $class;
        $this->traces = $traces;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getTraces()
    {
        return $this->traces;
    }
}
