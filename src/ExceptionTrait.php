<?php

declare(strict_types=1);

namespace Pkerrigan\Xray;

trait ExceptionTrait
{
    protected array $exceptions = [];

    public function addException(\Throwable $exception)
    {
        $this->exceptions[] = $exception;

        return $this;
    }

    protected function hasException(): bool
    {
        return count($this->exceptions) > 0;
    }

    protected function serialiseExceptionData()
    {
        if (!$this->hasException()) {
            return [];
        }

        $exceptions = array_map([$this, 'serializeException'], $this->exceptions);
        $exceptions = array_merge(...$exceptions);

        return array_filter([
            'working_directory' => realpath(__DIR__),
            'exceptions' => $exceptions,
        ]);
    }

    private function exceptionId(\Throwable $throwable)
    {
        return bin2hex(spl_object_hash($throwable));
    }

    private function serializeException(\Throwable $throwable)
    {
        return array_merge([
            array_filter([
                'id' => $this->exceptionId($throwable),
                'message' => $throwable->getMessage(),
                'type' => get_class($throwable),
                'cause' => $throwable->getPrevious() ? $this->exceptionId($throwable->getPrevious()) : null,
                'stack' => $this->serializeStacktrace($throwable),
            ]),
        ], $throwable->getPrevious() ? $this->serializeException($throwable->getPrevious()) : []);
    }

    private function serializeStacktrace(\Throwable $throwable)
    {
        return array_map(fn (array $stackElement): array => array_filter([
            'path' => isset($stackElement['file']) ? $stackElement['file'] : '',
            'line' => isset($stackElement['line']) ? $stackElement['line'] : '',
            'label' => (isset($stackElement['class']) ? $stackElement['class'] : '') . (isset($stackElement['type']) ? $stackElement['type'] : '') . $stackElement['function'],
        ]), $throwable->getTrace());
    }
}
