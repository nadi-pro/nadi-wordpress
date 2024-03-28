<?php

namespace Nadi\WordPress\Handler;

use Illuminate\Support\Arr;
use Nadi\Data\ExceptionEntry;
use Nadi\Data\Type;
use Nadi\WordPress\Actions\ExceptionContext;
use Nadi\WordPress\Exceptions\WordPressException;

class HandleExceptionEvent extends Base
{
    public static function make(WordPressException $exception)
    {
        return (new self())->handle($exception);
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

        try {
            dd($this->getTransporter()->send());
        } catch (\Throwable $th) {
            dd($th);
        }
    }
}
