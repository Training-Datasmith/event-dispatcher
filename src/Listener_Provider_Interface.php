<?php

declare (strict_types=1);
namespace Psr\Event_Dispatcher;

/**
 * Maps an event object to the ordered list of listeners that should receive it.
 *
 * Implementations are responsible for matching listeners to events, typically
 * using PHP's type system (instanceof checks, class name lookups, or interface
 * hierarchies). A single listener may be returned for multiple event types if
 * it is registered against a shared parent class or interface.
 *
 * @since 1.0
 * @see https://www.php-fig.org/psr/psr-14/
 */
interface Listener_Provider_Interface
{
    /**
     * Returns all listeners applicable to the given event, in call order.
     *
     * Each callable in the returned iterable MUST accept the given event
     * as its sole argument. The dispatcher calls them in iteration order;
     * the provider controls priority by controlling the order of the iterable.
     *
     * Returning an empty iterable is valid and means no listeners are
     * registered for this event type.
     *
     * @param object $event The event for which to retrieve applicable listeners.
     *   Implementations SHOULD use the event's class and its parent classes /
     *   interfaces to find all matching listeners.
     *
     * @return iterable<callable> An iterable of callables, each accepting the
     *   event object as its single argument. May be an array, a generator, or
     *   any Traversable. The iteration order defines the invocation order.
     *
     * @since 1.0
     */
    public function get_listeners_for_event(object $event): iterable;
}