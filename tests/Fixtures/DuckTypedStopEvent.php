<?php

declare(strict_types=1);

namespace Psr\EventDispatcher\Tests\Fixtures;

/**
 * Has isPropagationStopped() but does not implement StoppableEventInterface.
 */
final class DuckTypedStopEvent
{
    public function isPropagationStopped(): bool
    {
        return true;
    }
}
