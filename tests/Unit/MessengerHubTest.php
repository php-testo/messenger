<?php

declare(strict_types=1);

namespace Tests\Messenger\Unit;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Core\Log\Level;
use Testo\Core\Log\MessageLog;
use Testo\Messenger;
use Testo\Messenger\Channel;
use Testo\Messenger\Internal\MessengerHub;
use Testo\Test;
use Tests\Messenger\Stub\SpyDispatcher;

#[Test]
#[Covers(MessengerHub::class)]
final class MessengerHubTest
{
    public function logRecordsMessageAndDispatchesEvent(): void
    {
        $spy = new SpyDispatcher();
        $hub = new MessengerHub($spy);

        $hub->log('stdout', 'hello');

        Assert::count($hub->getMessages(), 1);
        Assert::count($spy->messages(), 1);
        Assert::same($spy->messages()[0]->message->content, 'hello');
        Assert::same($spy->messages()[0]->message->channel, 'stdout');
    }

    public function logDefaultsToInfoLevel(): void
    {
        $hub = new MessengerHub(new SpyDispatcher());
        $hub->log('c', 'x');
        Assert::same($hub->getMessages()->all()[0]->level, Level::Info);
    }

    public function getMessagesReturnsMessageLog(): void
    {
        $hub = new MessengerHub(new SpyDispatcher());
        Assert::instanceOf($hub->getMessages(), MessageLog::class);
    }

    public function channelReturnsHandleBoundToName(): void
    {
        $hub = new MessengerHub(new SpyDispatcher());
        $channel = $hub->channel('sql');
        Assert::instanceOf($channel, Channel::class);
        Assert::same($channel->name, 'sql');
    }

    public function scopeIsolatesChildBufferFromParent(): void
    {
        $hub = new MessengerHub(new SpyDispatcher());
        $hub->log('c', 'root');

        $inside = $hub->scope(static function (Messenger $m): MessageLog {
            $m->log('c', 'inner');
            return $m->getMessages();
        });

        Assert::count($inside, 1);
        Assert::same($inside->all()[0]->content, 'inner');
        Assert::count($hub->getMessages(), 1);
        Assert::same($hub->getMessages()->all()[0]->content, 'root');
    }

    public function eventStreamStaysGlobalAcrossScopes(): void
    {
        $spy = new SpyDispatcher();
        $hub = new MessengerHub($spy);

        $hub->log('c', 'a');
        $hub->scope(static fn(Messenger $m) => $m->log('c', 'b'));

        Assert::count($spy->messages(), 2);
    }

    public function scopeRestoresParentStateAcrossFiberSuspension(): void
    {
        $hub = new MessengerHub(new SpyDispatcher());
        $hub->log('c', 'root');

        $fiber = new \Fiber(static fn(): int => $hub->scope(static function (Messenger $m): int {
            $m->log('c', 'a');
            \Fiber::suspend();
            $m->log('c', 'b');
            return \count($m->getMessages());
        }));

        $fiber->start();
        # While the test is suspended the active scope is the parent again.
        $hub->log('c', 'while-suspended');
        $fiber->resume();

        Assert::same($fiber->getReturn(), 2);
        Assert::count($hub->getMessages(), 2);
    }
}
