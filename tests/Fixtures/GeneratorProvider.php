<?php

declare(strict_types=1);

namespace Psr\EventDispatcher\Tests\Fixtures;

use Psr\EventDispatcher\ListenerProviderInterface;

final class GeneratorProvider implements ListenerProviderInterface
{
    /** @var callable[] */
    private $listeners;

    /**
     * @param callable[] $listeners
     */
    public function __construct(array $listeners)
    {
        $this->listeners = $listeners;
    }

    public function getListenersForEvent(object $event): iterable
    {
        foreach ($this->listeners as $listener) {
            yield $listener;
        }
    }
}
