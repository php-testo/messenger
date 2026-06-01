<?php

declare(strict_types=1);

namespace Testo\Messenger\Internal;

use Psr\EventDispatcher\EventDispatcherInterface;
use Testo\Core\Log\Level;
use Testo\Core\Log\Message;
use Testo\Core\Log\MessageLog;
use Testo\Event\Message\MessageReceived;
use Testo\Messenger;
use Testo\Messenger\Channel;

/**
 * Default {@see Messenger} implementation.
 *
 * State is held in a separate {@see State} object and swapped during {@see scope()}, mirroring
 * the state/scope model of {@see \Internal\Container\ObjectContainer}: each scope owns an
 * isolated message buffer, while the {@see MessageReceived} event stream stays global.
 *
 * @internal
 */
final class MessengerHub implements Messenger
{
    private State $state;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        $this->state = new State();
    }

    #[\Override]
    public function log(string $channel, string $content, Level $level = Level::Info, array $context = []): void
    {
        $state = $this->state;

        // Avalanche guard: a listener that emits output during dispatch would be re-captured and
        // dispatched again, recursing forever. The flag lives on the State, so scope()'s state swap
        // keeps it fiber-local — other fibers running while this dispatch is suspended capture normally.
        if ($state->isSuspended()) {
            return;
        }

        $message = new Message(\microtime(true), $channel, $level, $content, $context);
        $state->push($message);

        $state->suspend();
        try {
            $this->eventDispatcher->dispatch(new MessageReceived($message));
        } finally {
            $state->resume();
        }
    }

    #[\Override]
    public function channel(string $name): Channel
    {
        return new Channel($this, $name);
    }

    #[\Override]
    public function scope(\Closure $scope): mixed
    {
        $old = $this->state;
        $new = $old->fork();
        try {
            $this->state = $new;
            if (\Fiber::getCurrent() === null) {
                return $scope($this);
            }

            // Wrap scope into a fiber so the parent state is restored across suspensions.
            $self = $this;
            $fiber = new \Fiber(static fn() => $scope($self));
            $value = $fiber->start();
            while (!$fiber->isTerminated()) {
                $this->state = $old;
                try {
                    $resume = \Fiber::suspend($value);
                } catch (\Throwable $e) {
                    $this->state = $new;
                    $value = $fiber->throw($e);
                    continue;
                }

                $this->state = $new;
                $value = $fiber->resume($resume);
            }

            return $fiber->getReturn();
        } finally {
            $this->state = $old;
            $new->destroy();
        }
    }

    #[\Override]
    public function getMessages(): MessageLog
    {
        return new MessageLog($this->state->getMessages());
    }
}
