<?php

declare(strict_types=1);

namespace Psr\EventDispatcher\Tests\Fixtures;

use Psr\EventDispatcher\StoppableEventInterface;

final class AlreadyStoppedEvent implements StoppableEventInterface
{
    public function isPropagationStopped(): bool
    {
        return true;
    }
}
