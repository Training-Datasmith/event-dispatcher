<?php

declare(strict_types=1);

namespace Psr\EventDispatcher\Tests;

use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionClass;
use ReflectionMethod;
use TypeError;

final class EventDispatcherInterfaceTest extends TestCase
{
    public function testIsInterfaceWithNoParents(): void
    {
        $this->assertTrue(interface_exists(EventDispatcherInterface::class));
        $reflection = new ReflectionClass(EventDispatcherInterface::class);
        $this->assertTrue($reflection->isInterface());
        $this->assertSame([], $reflection->getInterfaceNames());
        $this->assertCount(0, $reflection->getConstants());
        $this->assertCount(1, $reflection->getMethods());
        $this->assertSame('dispatch', $reflection->getMethods()[0]->getName());
    }

    public function testSourceIsStrictTypes(): void
    {
        $source = file_get_contents(__DIR__ . '/../src/EventDispatcherInterface.php');
        $this->assertIsString($source);
        $this->assertStringContainsString('declare(strict_types=1);', $source);
    }

    public function testDispatchSignature(): void
    {
        $method = new ReflectionMethod(EventDispatcherInterface::class, 'dispatch');
        $this->assertTrue($method->isPublic());
        $this->assertFalse($method->isStatic());
        $this->assertCount(1, $method->getParameters());
        $event = $method->getParameters()[0];
        $this->assertSame('event', $event->getName());
        $this->assertTrue($event->hasType());
        $this->assertSame('object', (string) $event->getType());
        $this->assertFalse($event->allowsNull());
        $this->assertFalse($event->isPassedByReference());
        $this->assertFalse($method->isVariadic());
        $this->assertFalse($method->hasReturnType());
    }

    public function testDispatchDocblockRequiresReturningTheSameEvent(): void
    {
        $method = new ReflectionMethod(EventDispatcherInterface::class, 'dispatch');
        $doc = $method->getDocComment();
        $this->assertIsString($doc);
        $this->assertStringContainsString('@return object', $doc);
        $this->assertStringContainsString('The Event that was passed', $doc);
    }

    public function testImplementationMayOmitReturnType(): void
    {
        $dispatcher = new class implements EventDispatcherInterface {
            public function dispatch(object $event)
            {
                return $event;
            }
        };
        $event = new \stdClass();
        $this->assertSame($event, $dispatcher->dispatch($event));
    }

    public function testImplementationMayAddObjectReturnType(): void
    {
        $dispatcher = new class implements EventDispatcherInterface {
            public function dispatch(object $event): object
            {
                return $event;
            }
        };
        $event = new \stdClass();
        $this->assertSame($event, $dispatcher->dispatch($event));
    }

    public function testDispatchRejectsNonObject(): void
    {
        $dispatcher = new class implements EventDispatcherInterface {
            public function dispatch(object $event)
            {
                return $event;
            }
        };

        $this->expectException(TypeError::class);
        $dispatcher->dispatch('not-an-object');
    }

    public function testDispatchRejectsNull(): void
    {
        $dispatcher = new class implements EventDispatcherInterface {
            public function dispatch(object $event)
            {
                return $event;
            }
        };

        $this->expectException(TypeError::class);
        $dispatcher->dispatch(null);
    }

    public function testDispatchRejectsArray(): void
    {
        $dispatcher = new class implements EventDispatcherInterface {
            public function dispatch(object $event)
            {
                return $event;
            }
        };

        $this->expectException(TypeError::class);
        $dispatcher->dispatch([]);
    }
}
