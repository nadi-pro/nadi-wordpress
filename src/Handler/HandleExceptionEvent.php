<?php

namespace Nadi\WordPress\Handler;

use Error;
use Illuminate\Support\Arr;
use Nadi\Data\ExceptionEntry;
use Nadi\Data\Type;
use Nadi\WordPress\Actions\ExceptionContext;
use Nadi\WordPress\Exceptions\WordPressException;

class HandleExceptionEvent extends Base
{
    public static function make(Error|WordPressException $exception)
    {
        if ($exception instanceof WordPressException) {
            return (new self())->handle($exception);
        }

        return (new self())->handle(new WordPressException(
            $exception->getTrace(),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getCode()
        ));
    }

    /**
     * Handle the event.
     */
    public function handle(WordPressException $exception): void
    {
        $trace = collect($exception->getTrace())->map(function ($item) {
            return Arr::only($item, ['file', 'line']);
        })->toArray();

        $this->store(
            ExceptionEntry::make(
                $exception,
                Type::EXCEPTION,
                [
                    'class' => $exception->getClass(),
                    'file' => $exception->file,
                    'line' => $exception->line,
                    'message' => $exception->getMessage(),
                    'context' => $this->getUser(), // @todo
                    'trace' => $trace,
                    'line_preview' => ExceptionContext::get($exception),
                ]
            )->setHashFamily(
                $this->hash(
                    get_class($exception).
                    $exception->file.
                    $exception->line.
                    $exception->getMessage().
                    date('Y-m-d'))
            )->tags(
                [
                    'type' => 'exception',
                    'environment' => $this->getEnvironment(),
                ]
            )->toArray()
        );

        $this->getTransporter()->send();
    }
}
