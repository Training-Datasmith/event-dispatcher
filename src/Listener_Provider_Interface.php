<?php

declare (strict_types=1);
namespace Psr\Event_Dispatcher;

/**
 * Mapper from an event to the listeners that are applicable to that event.
 */
interface Listener_Provider_Interface
{
    /**
     * @param object $event
     *   An event for which to return the relevant listeners.
     * @return iterable<callable>
     *   An iterable (array, iterator, or generator) of callables.  Each
     *   callable MUST be type-compatible with $event.
     */
    public function get_listeners_for_event(object $event): iterable;
}