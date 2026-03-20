<?php

declare (strict_types=1);
namespace Psr\Event_Dispatcher;

/**
 * An event whose propagation through the listener chain can be halted.
 *
 * When a listener decides the event has been fully handled, it can signal
 * the dispatcher to skip remaining listeners by calling a stop method (or
 * setting an internal flag) on the event. The dispatcher MUST check
 * is_propagation_stopped() after each listener invocation and stop the loop
 * if it returns true.
 *
 * Implementing this interface is optional. Events that do not need early
 * termination should not implement it; the dispatcher MUST still process
 * all listeners for non-stoppable events.
 *
 * @since 1.0
 * @see https://www.php-fig.org/psr/psr-14/
 */
interface Stoppable_Event_Interface
{
    /**
     * Returns whether propagation has been stopped for this event.
     *
     * This will typically only be called by the Dispatcher after each listener
     * returns, to determine whether to continue invoking remaining listeners.
     * Listeners themselves SHOULD NOT call this method; they stop propagation
     * by mutating the event's internal flag via a domain-specific method (e.g.,
     * stopPropagation() or handled()).
     *
     * @return bool True if the Dispatcher should stop calling further listeners
     *   for this event. False to continue propagation through the listener chain.
     *
     * @since 1.0
     */
    public function is_propagation_stopped(): bool;
}