<?php

declare(strict_types=1);

namespace Psr\EventDispatcher\Tests;

use PHPUnit\Framework\TestCase;

final class IncompatibleImplementationTest extends TestCase
{
    public function testDispatchParameterCannotNarrowObject(): void
    {
        $this->assertIncompatibleImplementation(<<<'PHP'
class IncompatibleDispatcher implements \Psr\EventDispatcher\EventDispatcherInterface
{
    public function dispatch(string $event)
    {
        return $event;
    }
}
PHP
        );
    }

    public function testGetListenersForEventMustReturnIterable(): void
    {
        $this->assertIncompatibleImplementation(<<<'PHP'
class IncompatibleListenerProvider implements \Psr\EventDispatcher\ListenerProviderInterface
{
    public function getListenersForEvent(object $event): string
    {
        return '';
    }
}
PHP
        );
    }

    public function testIsPropagationStoppedMustReturnBool(): void
    {
        $this->assertIncompatibleImplementation(<<<'PHP'
class IncompatibleStoppableEvent implements \Psr\EventDispatcher\StoppableEventInterface
{
    public function isPropagationStopped(): int
    {
        return 0;
    }
}
PHP
        );
    }

    private function assertIncompatibleImplementation(string $classDefinition): void
    {
        $autoload = dirname(__DIR__) . '/vendor/autoload.php';
        $script = "<?php\nrequire " . var_export($autoload, true) . ";\n" . $classDefinition . "\n";

        $file = sys_get_temp_dir() . '/psr14_incompatible_' . uniqid('', true) . '.php';
        file_put_contents($file, $script);

        $command = 'php ' . escapeshellarg($file) . ' 2>&1';
        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        @unlink($file);

        $this->assertNotSame(0, $exitCode, 'Expected fatal error loading incompatible class');
        $joined = implode("\n", $output);
        $this->assertRegExp('/not compatible|must be compatible/i', $joined, $joined);
    }
}
