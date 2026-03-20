# Architecture: psr/event-dispatcher (PSR-14)

## Purpose

This package defines PSR-14: Event Dispatcher. It provides a standard contract
for event-driven communication within PHP applications, enabling decoupled
components to communicate without direct dependencies on each other.

## PSR Standard

**PSR-14** — https://www.php-fig.org/psr/psr-14/

## Directory Structure

```
src/
  Event_Dispatcher_Interface.php    — Accepts an event and passes it to listeners
  Listener_Provider_Interface.php   — Maps event types to their listener callables
  Stoppable_Event_Interface.php     — Optional; allows a listener to halt propagation
```

## Key Design Decisions

### Dispatcher and provider are separate
The dispatcher (Event_Dispatcher_Interface) handles calling listeners in order.
The listener provider (Listener_Provider_Interface) handles mapping event types
to listeners. This separation allows each to be replaced or decorated independently,
e.g., a caching provider wrapper or a prioritising dispatcher decorator.

### Events are plain objects
PSR-14 does not require events to extend a base class or implement any interface
(unless they want stoppable behaviour). Any PHP object can be an event, making
integration with existing domain objects straightforward.

### Propagation stopping is opt-in
Only events that implement Stoppable_Event_Interface can halt the listener chain.
The dispatcher MUST check is_propagation_stopped() after each listener only when
the event implements that interface; this avoids an interface check cost on every
event dispatch for non-stoppable events.

### Listeners receive the same object
All listeners for an event receive the same mutable object instance. Listeners
may add derived data to the event (enrichment pattern) or mark it as handled
(stoppable pattern).

## Extension Points

- Implement `Event_Dispatcher_Interface` to add features like async dispatch,
  middleware, logging, or transaction-aware dispatching.
- Implement `Listener_Provider_Interface` to add priority queues, lazy loading
  of listeners from a DI container, or attribute-based listener discovery.
- Implement `Stoppable_Event_Interface` on domain event classes that need
  short-circuit behaviour.

## Dependency Flow

```
Calling code
    └── Event_Dispatcher_Interface  (injected)
            └── Listener_Provider_Interface  (injected into dispatcher)
                    └── callable[]  (the actual listeners)
```
