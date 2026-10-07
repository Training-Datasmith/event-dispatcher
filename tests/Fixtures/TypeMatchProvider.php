<?php

declare(strict_types=1);

namespace Psr\EventDispatcher\Tests\Fixtures;

use Psr\EventDispatcher\ListenerProviderInterface;

final class TypeMatchProvider implements ListenerProviderInterface
{
    /** @var callable */
    private $forA;

    /** @var callable */
    private $forUnrelated;

    public function __construct(callable $forA, callable $forUnrelated)
    {
        $this->forA = $forA;
        $this->forUnrelated = $forUnrelated;
    }

    public function getListenersForEvent(object $event): iterable
    {
        if ($event instanceof EventA) {
            return [$this->forA];
        }

        return [$this->forUnrelated];
    }
}
