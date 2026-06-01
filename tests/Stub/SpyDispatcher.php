<?php

declare(strict_types=1);

namespace Tests\Messenger\Stub;

use Psr\EventDispatcher\EventDispatcherInterface;
use Testo\Event\Message\MessageReceived;

/**
 * Records every dispatched event so tests can assert on the message firehose.
 */
final class SpyDispatcher implements EventDispatcherInterface
{
    /** @var list<object> */
    public array $events = [];

    public function dispatch(object $event): object
    {
        $this->events[] = $event;
        return $event;
    }

    /**
     * @return list<MessageReceived>
     */
    public function messages(): array
    {
        return \array_values(\array_filter(
            $this->events,
            static fn(object $event): bool => $event instanceof MessageReceived,
        ));
    }
}
