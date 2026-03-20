<?php

declare (strict_types=1);
namespace Psr\Event_Dispatcher;

/**
 * Dispatches events to all registered listeners.
 *
 * The dispatcher is responsible for retrieving listeners from a
 * Listener_Provider_Interface and calling each one in sequence. If the event
 * implements Stoppable_Event_Interface, the dispatcher MUST check
 * is_propagation_stopped() after each listener and stop calling further
 * listeners if it returns true.
 *
 * @since 1.0
 * @see https://www.php-fig.org/psr/psr-14/
 */
interface Event_Dispatcher_Interface
{
    /**
     * Provide all relevant listeners with an event to process.
     *
     * The dispatcher MUST pass the same event object to each listener in turn.
     * Listeners may mutate the event (e.g., add derived data). The final
     * state of the event is returned so callers can inspect any modifications
     * made by listeners.
     *
     * If the event implements Stoppable_Event_Interface and a listener calls
     * the stop method, the dispatcher MUST NOT invoke any subsequent listeners.
     *
     * @param object $event The event object to dispatch. Can be any object;
     *   listeners are matched by type from the Listener_Provider_Interface.
     *
     * @return object The same event instance that was passed in, potentially
     *   mutated by one or more listeners. The return type is the same object
     *   identity as $event (not a clone).
     *
     * @since 1.0
     */
    public function dispatch(object $event): object;
}