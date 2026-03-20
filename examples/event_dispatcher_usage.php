<?php

declare(strict_types=1);

/**
 * Example: PSR-14 event dispatching with a stoppable event.
 *
 * Shows the canonical three-part pattern:
 *   1. A plain event object (optionally stoppable).
 *   2. A listener provider that maps event types to callables.
 *   3. A dispatcher that iterates listeners and respects stop signals.
 */

use Psr\EventDispatcher\Event_Dispatcher_Interface;
use Psr\EventDispatcher\Listener_Provider_Interface;
use Psr\EventDispatcher\Stoppable_Event_Interface;

// --- Domain event ---

final class User_Registered_Event implements Stoppable_Event_Interface
{
    private bool $stopped = false;

    public function __construct(
        public readonly string $email,
        public readonly int    $user_id,
    ) {}

    public function stop_propagation(): void
    {
        $this->stopped = true;
    }

    public function is_propagation_stopped(): bool
    {
        return $this->stopped;
    }
}

// --- Simple listener provider ---

final class Simple_Listener_Provider implements Listener_Provider_Interface
{
    /** @var array<class-string, list<callable>> */
    private array $listeners = [];

    public function add_listener(string $event_class, callable $listener): void
    {
        $this->listeners[$event_class][] = $listener;
    }

    public function get_listeners_for_event(object $event): iterable
    {
        $class = $event::class;
        return $this->listeners[$class] ?? [];
    }
}

// --- Simple dispatcher ---

final class Simple_Dispatcher implements Event_Dispatcher_Interface
{
    public function __construct(
        private readonly Listener_Provider_Interface $provider,
    ) {}

    public function dispatch(object $event): object
    {
        foreach ($this->provider->get_listeners_for_event($event) as $listener) {
            if ($event instanceof Stoppable_Event_Interface
                && $event->is_propagation_stopped()
            ) {
                break;
            }
            $listener($event);
        }
        return $event;
    }
}

// --- Wiring ---

$provider = new Simple_Listener_Provider();

// Listener 1: send a welcome email.
$provider->add_listener(User_Registered_Event::class, function (User_Registered_Event $event): void {
    echo "Sending welcome email to {$event->email}\n";
});

// Listener 2: log the registration, then stop further processing.
$provider->add_listener(User_Registered_Event::class, function (User_Registered_Event $event): void {
    echo "Logging user #{$event->user_id} registration\n";
    $event->stop_propagation();
});

// Listener 3: this will never be called because listener 2 stopped propagation.
$provider->add_listener(User_Registered_Event::class, function (User_Registered_Event $event): void {
    echo "This should NOT print\n";
});

$dispatcher = new Simple_Dispatcher($provider);
$dispatcher->dispatch(new User_Registered_Event('alice@example.com', 42));

// Output:
// Sending welcome email to alice@example.com
// Logging user #42 registration
