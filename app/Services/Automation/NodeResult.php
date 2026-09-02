<?php

namespace App\Services\Automation;

class NodeResult
{
    public function __construct(
        public bool $finished = false,
        public bool $waiting = false,
        public ?string $handle = null,
        public array $output = [],
        public ?string $status = null,
    ) {
    }

    public static function continue(
        ?string $handle = null,
        array $output = []
    ): static {
        return new static(
            finished: false,
            waiting: false,
            handle: $handle,
            output: $output,
            status: null,
        );
    }

    public static function finish(
        array $output = [],
        ?string $status = null
    ): static {
        return new static(
            finished: true,
            waiting: false,
            output: $output,
            status: $status,
        );
    }

    public static function wait(
        array $output = []
    ): static {
        return new static(
            finished: false,
            waiting: true,
            output: $output,
            status: null,
        );
    }

    public function isFinished(): bool
    {
        return $this->finished;
    }

    public function isWaiting(): bool
    {
        return $this->waiting;
    }

    public function shouldFollow(
        ?string $sourceHandle
    ): bool {
        if (
            $this->handle === null &&
            $sourceHandle === null
        ) {
            return true;
        }

        return $this->handle === $sourceHandle;
    }
}