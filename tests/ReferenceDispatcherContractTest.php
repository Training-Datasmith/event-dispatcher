<?php

declare(strict_types=1);

namespace Psr\EventDispatcher\Tests;

use Error;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\Tests\Fixtures\AlreadyStoppedEvent;
use Psr\EventDispatcher\Tests\Fixtures\DuckTypedStopEvent;
use Psr\EventDispatcher\Tests\Fixtures\EventA;
use Psr\EventDispatcher\Tests\Fixtures\EventB;
use Psr\EventDispatcher\Tests\Fixtures\GeneratorProvider;
use Psr\EventDispatcher\Tests\Fixtures\ListProvider;
use Psr\EventDispatcher\Tests\Fixtures\PlainEvent;
use Psr\EventDispatcher\Tests\Fixtures\ReferenceDispatcher;
use Psr\EventDispatcher\Tests\Fixtures\StoppableEvent;
use Psr\EventDispatcher\Tests\Fixtures\TypeMatchProvider;
use Psr\EventDispatcher\Tests\Fixtures\UnrelatedEvent;
use RuntimeException;
use TypeError;

final class ReferenceDispatcherContractTest extends TestCase
{
    /** @var list<string> */
    private $listenerCallOrder = [];

    protected function setUp(): void
    {
        $this->listenerCallOrder = [];
    }

    public function testReturnsSameInstance(): void
    {
        $event = new PlainEvent();
        $dispatcher = new ReferenceDispatcher(new ListProvider([]));

        $this->assertSame($event, $dispatcher->dispatch($event));
    }

    public function testListenersReceiveThatInstanceAndMutationsAreVisibleToTheEmitter(): void
    {
        $event = new PlainEvent();
        $dispatcher = new ReferenceDispatcher(new ListProvider([
            function (PlainEvent $e): void {
                $e->payload = 'mutated';
            },
        ]));

        $dispatcher->dispatch($event);
        $this->assertSame('mutated', $event->payload);
    }

    public function testListenersRunSynchronouslyInProviderOrder(): void
    {
        $dispatcher = new ReferenceDispatcher(new ListProvider([
            $this->spyListener('first'),
            $this->spyListener('second'),
            $this->spyListener('third'),
        ]));

        $dispatcher->dispatch(new PlainEvent());
        $this->assertSame(['first', 'second', 'third'], $this->listenerCallOrder);
    }

    public function testEachListenerIsCalledWithOnlyTheEvent(): void
    {
        $dispatcher = new ReferenceDispatcher(new ListProvider([
            function (PlainEvent $event): void {
                if (func_num_args() !== 1) {
                    throw new RuntimeException('expected one argument');
                }
            },
        ]));

        $dispatcher->dispatch(new PlainEvent());
        $this->assertSame([], $this->listenerCallOrder);
    }

    public function testGeneratorFromProviderIsConsumedInOrder(): void
    {
        $dispatcher = new ReferenceDispatcher(new GeneratorProvider([
            $this->spyListener('g1'),
            $this->spyListener('g2'),
        ]));

        $dispatcher->dispatch(new PlainEvent());
        $this->assertSame(['g1', 'g2'], $this->listenerCallOrder);
    }

    public function testEmptyListenerListReturnsTheEvent(): void
    {
        $event = new PlainEvent();
        $dispatcher = new ReferenceDispatcher(new ListProvider([]));

        $this->assertSame($event, $dispatcher->dispatch($event));
    }

    public function testListenerReturnValueIsIgnored(): void
    {
        $event = new PlainEvent();
        $replacement = new PlainEvent();
        $dispatcher = new ReferenceDispatcher(new ListProvider([
            function (PlainEvent $e) use ($replacement): PlainEvent {
                return $replacement;
            },
        ]));

        $this->assertSame($event, $dispatcher->dispatch($event));
    }

    public function testExceptionStopsLaterListenersAndPropagates(): void
    {
        $dispatcher = new ReferenceDispatcher(new ListProvider([
            $this->spyListener('before'),
            function (): void {
                throw new RuntimeException('listener failed');
            },
            $this->spyListener('after'),
        ]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('listener failed');

        try {
            $dispatcher->dispatch(new PlainEvent());
        } finally {
            $this->assertSame(['before'], $this->listenerCallOrder);
        }
    }

    public function testErrorStopsLaterListenersAndPropagates(): void
    {
        $dispatcher = new ReferenceDispatcher(new ListProvider([
            $this->spyListener('before'),
            function (): void {
                throw new class extends Error {
                };
            },
            $this->spyListener('after'),
        ]));

        $this->expectException(Error::class);

        try {
            $dispatcher->dispatch(new PlainEvent());
        } finally {
            $this->assertSame(['before'], $this->listenerCallOrder);
        }
    }

    public function testTypeErrorFromAListenerPropagates(): void
    {
        $dispatcher = new ReferenceDispatcher(new ListProvider([
            $this->spyListener('before'),
            function (UnrelatedEvent $event): void {
            },
            $this->spyListener('after'),
        ]));

        $this->expectException(TypeError::class);

        try {
            $dispatcher->dispatch(new PlainEvent());
        } finally {
            $this->assertSame(['before'], $this->listenerCallOrder);
        }
    }

    public function testAlreadyStoppedEventInvokesNoListeners(): void
    {
        $dispatcher = new ReferenceDispatcher(new ListProvider([
            $this->spyListener('never'),
        ]));

        $dispatcher->dispatch(new AlreadyStoppedEvent());
        $this->assertSame([], $this->listenerCallOrder);
    }

    public function testListenerThatStopsSkipsTheRemainder(): void
    {
        $dispatcher = new ReferenceDispatcher(new ListProvider([
            $this->spyListener('one'),
            function (StoppableEvent $event): void {
                $this->recordCall('two');
                $event->stopPropagation();
            },
            $this->spyListener('three'),
        ]));

        $dispatcher->dispatch(new StoppableEvent());
        $this->assertSame(['one', 'two'], $this->listenerCallOrder);
    }

    public function testDuckTypedStopMethodDoesNotStopDispatch(): void
    {
        $dispatcher = new ReferenceDispatcher(new ListProvider([
            $this->spyListener('a'),
            $this->spyListener('b'),
        ]));

        $dispatcher->dispatch(new DuckTypedStopEvent());
        $this->assertSame(['a', 'b'], $this->listenerCallOrder);
    }

    public function testReentrantDispatchFinishesTheInnerEventFirst(): void
    {
        $innerDispatcher = new ReferenceDispatcher(new ListProvider([
            $this->spyListener('inner'),
        ]));
        $outerDispatcher = new ReferenceDispatcher(new ListProvider([
            function (PlainEvent $event) use ($innerDispatcher): void {
                $this->recordCall('outer-before-inner');
                $innerDispatcher->dispatch(new PlainEvent());
                $this->recordCall('outer-after-inner');
            },
            $this->spyListener('outer-tail'),
        ]));

        $outerDispatcher->dispatch(new PlainEvent());
        $this->assertSame(
            ['outer-before-inner', 'inner', 'outer-after-inner', 'outer-tail'],
            $this->listenerCallOrder
        );
    }

    public function testParentTypeListenerIsApplicable(): void
    {
        $dispatcher = new ReferenceDispatcher(new TypeMatchProvider(
            $this->spyListener('for-a'),
            $this->spyListener('for-unrelated')
        ));

        $dispatcher->dispatch(new EventB());
        $this->assertSame(['for-a'], $this->listenerCallOrder);
    }

    private function spyListener(string $label): callable
    {
        return function () use ($label): void {
            $this->recordCall($label);
        };
    }

    private function recordCall(string $label): void
    {
        $this->listenerCallOrder[] = $label;
    }
}
