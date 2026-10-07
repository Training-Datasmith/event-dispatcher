<?php

declare(strict_types=1);

namespace Psr\EventDispatcher\Tests\Fixtures;

use Psr\EventDispatcher\ListenerProviderInterface;

final class ListProvider implements ListenerProviderInterface
{
    /** @var iterable<callable> */
    private $listeners;

    /**
     * @param iterable<callable>|callable[] $listeners
     */
    public function __construct(iterable $listeners)
    {
        $this->listeners = $listeners;
    }

    public function getListenersForEvent(object $event): iterable
    {
        return $this->listeners;
    }
}
