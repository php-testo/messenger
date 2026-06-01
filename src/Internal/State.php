<?php

declare(strict_types=1);

namespace Testo\Messenger\Internal;

use Testo\Core\Log\Message;

/**
 * Message buffer of a single {@see \Testo\Messenger} scope.
 *
 * Stores the messages recorded within one scope, plus a transient suspension flag used as an
 * avalanche guard while a {@see \Testo\Event\Message\MessageReceived} event is being dispatched.
 * Keeping the flag here (rather than on the hub) makes it fiber-local for free: `scope()` swaps the
 * active state across fiber suspensions, so a suspended state never blocks another fiber's output.
 *
 * Mirrors the state/scope model of {@see \Internal\Container\ObjectContainer}: each scope (suite /
 * case / test fork) owns an isolated buffer.
 *
 * @internal
 */
final class State
{
    /** @var list<\Testo\Core\Log\Message> */
    private array $messages = [];

    private bool $suspended = false;

    public function push(Message $message): void
    {
        $this->messages[] = $message;
    }

    public function isSuspended(): bool
    {
        return $this->suspended;
    }

    public function suspend(): void
    {
        $this->suspended = true;
    }

    public function resume(): void
    {
        $this->suspended = false;
    }

    /**
     * @return list<\Testo\Core\Log\Message>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Create a child state for a nested scope: a fresh, isolated buffer.
     */
    public function fork(): self
    {
        return new self();
    }

    public function destroy(): void
    {
        $this->messages = [];
    }
}
