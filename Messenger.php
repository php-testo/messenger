<?php

declare(strict_types=1);

namespace Testo;

use Testo\Core\Log\Level;
use Testo\Core\Log\Message;
use Testo\Core\Log\MessageLog;
use Testo\Event\Message\MessageReceived;
use Testo\Messenger\Channel;

/**
 * Central message hub.
 *
 * Producers — the output-buffer trap, test code, middleware, or other plugins — write
 * messages into named channels through this service. Every write is recorded into the active
 * scope's buffer and announced via a {@see MessageReceived} event.
 *
 * Forked per suite / case / test via {@see scope()}: each scope owns an isolated message buffer
 * while the {@see MessageReceived} event stream stays global.
 *
 * This interface is meant to be **consumed, not implemented** by userland code: inject or
 * type-hint it to talk to the messenger. The implementation is provided by the framework
 * ({@see \Testo\Messenger\Internal\MessengerHub}) — new methods may be added in minor releases,
 * which is safe for consumers but would break external implementations.
 *
 * @api
 */
interface Messenger
{
    /**
     * Record a message in the given channel.
     *
     * Builds a {@see Message}, stores it in the active scope's buffer and announces it via a
     * {@see MessageReceived} event (the global firehose — fired once per write, regardless of scope).
     *
     * @param non-empty-string $channel
     * @param array<string, mixed> $context Structured context attached to the message.
     */
    public function log(string $channel, string $content, Level $level = Level::Info, array $context = []): void;

    /**
     * Obtain a channel-bound writer handle.
     *
     * @param non-empty-string $name
     */
    public function channel(string $name): Channel;

    /**
     * Run the given closure within a forked scope.
     *
     * The active state is swapped for a fresh child for the duration of the call and restored
     * afterwards; the child (and its message buffer) is destroyed on exit. {@see getMessages()}
     * inside the closure observes only what was written within it. Fiber-aware: the parent state
     * is restored across suspension points.
     *
     * @template T
     * @param \Closure(self): T $scope
     * @return T
     */
    public function scope(\Closure $scope): mixed;

    /**
     * Messages recorded in the active scope.
     */
    public function getMessages(): MessageLog;
}
