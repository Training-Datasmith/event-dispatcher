<?php

declare(strict_types=1);

namespace Psr\EventDispatcher\Tests;

use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\Tests\Fixtures\PlainEvent;
use ReflectionClass;
use ReflectionMethod;
use TypeError;

final class ListenerProviderInterfaceTest extends TestCase
{
    /** @var list<string> */
    private $spyCalls = [];

    protected function setUp(): void
    {
        $this->spyCalls = [];
    }

    public function testIsInterfaceWithNoParents(): void
    {
        $this->assertTrue(interface_exists(ListenerProviderInterface::class));
        $reflection = new ReflectionClass(ListenerProviderInterface::class);
        $this->assertTrue($reflection->isInterface());
        $this->assertSame([], $reflection->getInterfaceNames());
        $this->assertCount(0, $reflection->getConstants());
        $this->assertCount(1, $reflection->getMethods());
        $this->assertSame('getListenersForEvent', $reflection->getMethods()[0]->getName());
    }

    public function testSourceIsStrictTypes(): void
    {
        $source = file_get_contents(__DIR__ . '/../src/ListenerProviderInterface.php');
        $this->assertIsString($source);
        $this->assertStringContainsString('declare(strict_types=1);', $source);
    }

    public function testGetListenersForEventSignature(): void
    {
        $method = new ReflectionMethod(ListenerProviderInterface::class, 'getListenersForEvent');
        $this->assertTrue($method->isPublic());
        $this->assertCount(1, $method->getParameters());
        $event = $method->getParameters()[0];
        $this->assertSame('event', $event->getName());
        $this->assertTrue($event->hasType());
        $this->assertSame('object', (string) $event->getType());
        $this->assertFalse($event->allowsNull());
        $this->assertTrue($method->hasReturnType());
        $this->assertSame('iterable', (string) $method->getReturnType());
        $this->assertFalse($method->getReturnType()->allowsNull());
    }

    public function testDocblockRequiresTypeCompatibleCallables(): void
    {
        $method = new ReflectionMethod(ListenerProviderInterface::class, 'getListenersForEvent');
        $doc = $method->getDocComment();
        $this->assertIsString($doc);
        $this->assertStringContainsString('iterable<callable>', $doc);
        $this->assertStringContainsString('type-compatible', $doc);
    }

    public function testArrayImplementationIsValid(): void
    {
        $provider = new class implements ListenerProviderInterface {
            public function getListenersForEvent(object $event): iterable
            {
                return [];
            }
        };
        $this->assertInstanceOf(ListenerProviderInterface::class, $provider);
        $this->assertSame([], $this->materializeListeners($provider->getListenersForEvent(new PlainEvent())));
    }

    public function testGeneratorImplementationIsValid(): void
    {
        $provider = new class implements ListenerProviderInterface {
            public function getListenersForEvent(object $event): iterable
            {
                if (false) {
                    yield function (): void {
                    };
                }
            }
        };
        $this->assertInstanceOf(ListenerProviderInterface::class, $provider);
        $this->assertSame([], $this->materializeListeners($provider->getListenersForEvent(new PlainEvent())));
    }

    public function testListingListenersDoesNotInvokeThem(): void
    {
        $provider = new class($this) implements ListenerProviderInterface {
            /** @var ListenerProviderInterfaceTest */
            private $test;

            public function __construct(ListenerProviderInterfaceTest $test)
            {
                $this->test = $test;
            }

            public function getListenersForEvent(object $event): iterable
            {
                $test = $this->test;

                return [
                    function () use ($test): void {
                        $test->recordSpy('ran');
                    },
                ];
            }
        };

        $this->materializeListeners($provider->getListenersForEvent(new PlainEvent()));
        $this->assertSame([], $this->spyCalls);
    }

    public function testGetListenersForEventRejectsNonObject(): void
    {
        $provider = new class implements ListenerProviderInterface {
            public function getListenersForEvent(object $event): iterable
            {
                return [];
            }
        };

        $this->expectException(TypeError::class);
        $provider->getListenersForEvent('not-an-object');
    }

    public function testGetListenersForEventRejectsNull(): void
    {
        $provider = new class implements ListenerProviderInterface {
            public function getListenersForEvent(object $event): iterable
            {
                return [];
            }
        };

        $this->expectException(TypeError::class);
        $provider->getListenersForEvent(null);
    }

    public function testGetListenersForEventRejectsArray(): void
    {
        $provider = new class implements ListenerProviderInterface {
            public function getListenersForEvent(object $event): iterable
            {
                return [];
            }
        };

        $this->expectException(TypeError::class);
        $provider->getListenersForEvent([]);
    }

    public function recordSpy(string $label): void
    {
        $this->spyCalls[] = $label;
    }

    /**
     * @param iterable<callable> $listeners
     *
     * @return list<callable>
     */
    private function materializeListeners(iterable $listeners): array
    {
        if (is_array($listeners)) {
            return $listeners;
        }

        return iterator_to_array($listeners);
    }
}
