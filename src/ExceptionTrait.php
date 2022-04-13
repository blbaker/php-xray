<?php

declare(strict_types=1);

namespace Pkerrigan\Xray;

trait ExceptionTrait
{
    protected array $exceptions = [];

    public function addException(\Throwable $exception): self
    {
        $this->exceptions[] = $exception;

        return $this;
    }

    protected function hasException(): bool
    {
        return count($this->exceptions) > 0;
    }

    protected function serialiseExceptionData(): array
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

    private function exceptionId(\Throwable $throwable): string
    {
        return bin2hex(spl_object_hash($throwable));
    }

    private function serializeException(\Throwable $throwable): array
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

    private function serializeStacktrace(\Throwable $throwable): array
    {
        return array_map(fn(array $stackElement): array => array_filter([
            'path' => $stackElement['file'],
            'line' => $stackElement['line'],
            'label' => ($stackElement['class'] ?? '').($stackElement['type'] ?? '').$stackElement['function'],
        ]), $throwable->getTrace());
    }
}
