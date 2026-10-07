<?php

declare(strict_types=1);

namespace Psr\EventDispatcher\Tests;

use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\StoppableEventInterface;
use ReflectionClass;
use ReflectionMethod;
use TypeError;

final class StoppableEventInterfaceTest extends TestCase
{
    public function testIsInterfaceWithNoParents(): void
    {
        $this->assertTrue(interface_exists(StoppableEventInterface::class));
        $reflection = new ReflectionClass(StoppableEventInterface::class);
        $this->assertTrue($reflection->isInterface());
        $this->assertSame([], $reflection->getInterfaceNames());
        $this->assertCount(0, $reflection->getConstants());
        $this->assertCount(1, $reflection->getMethods());
        $this->assertSame('isPropagationStopped', $reflection->getMethods()[0]->getName());
        $this->assertFalse($reflection->hasMethod('stopPropagation'));
    }

    public function testSourceIsStrictTypes(): void
    {
        $source = file_get_contents(__DIR__ . '/../src/StoppableEventInterface.php');
        $this->assertIsString($source);
        $this->assertStringContainsString('declare(strict_types=1);', $source);
    }

    public function testIsPropagationStoppedSignature(): void
    {
        $method = new ReflectionMethod(StoppableEventInterface::class, 'isPropagationStopped');
        $this->assertTrue($method->isPublic());
        $this->assertCount(0, $method->getParameters());
        $this->assertTrue($method->hasReturnType());
        $this->assertSame('bool', (string) $method->getReturnType());
        $this->assertFalse($method->getReturnType()->allowsNull());
    }

    public function testDocblockDefinesStoppedAndContinue(): void
    {
        $method = new ReflectionMethod(StoppableEventInterface::class, 'isPropagationStopped');
        $doc = $method->getDocComment();
        $this->assertIsString($doc);
        $this->assertStringContainsString(
            'True if the Event is complete and no further listeners should be called',
            $doc
        );
        $this->assertStringContainsString('False to continue calling listeners', $doc);
    }

    public function testBoolReturnIsAccepted(): void
    {
        $stopped = new class implements StoppableEventInterface {
            public function isPropagationStopped(): bool
            {
                return true;
            }
        };
        $continuing = new class implements StoppableEventInterface {
            public function isPropagationStopped(): bool
            {
                return false;
            }
        };
        $this->assertTrue($stopped->isPropagationStopped());
        $this->assertFalse($continuing->isPropagationStopped());
    }

    public function testNonBoolReturnIsRejected(): void
    {
        $event = new class implements StoppableEventInterface {
            public function isPropagationStopped(): bool
            {
                return 1;
            }
        };

        $this->expectException(TypeError::class);
        $event->isPropagationStopped();
    }
}
