<?php

declare(strict_types=1);

namespace Tests\Messenger\Unit;

use Psr\Log\LoggerInterface;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Core\Log\Level;
use Testo\Core\Log\Message;
use Testo\Messenger\Channel;
use Testo\Messenger\Internal\MessengerHub;
use Testo\Test;
use Tests\Messenger\Stub\SpyDispatcher;

#[Test]
#[Covers(Channel::class)]
final class ChannelTest
{
    public function isPsr3Logger(): void
    {
        Assert::instanceOf($this->hub()->channel('app'), LoggerInterface::class);
    }

    public function writeRoutesMessageIntoTheChannel(): void
    {
        $hub = $this->hub();
        $hub->channel('app')->write('payload', Level::Warning, ['k' => 'v']);

        $message = $hub->getMessages()->all()[0];
        Assert::same($message->channel, 'app');
        Assert::same($message->content, 'payload');
        Assert::same($message->level, Level::Warning);
        Assert::same($message->context, ['k' => 'v']);
    }

    public function writeDefaultsToInfoLevel(): void
    {
        $hub = $this->hub();
        $hub->channel('app')->write('x');
        Assert::same($hub->getMessages()->all()[0]->level, Level::Info);
    }

    public function psrLevelMethodsMapToEnum(): void
    {
        $hub = $this->hub();
        $channel = $hub->channel('app');
        $channel->error('e');
        $channel->warning('w');
        $channel->debug('d');

        $levels = \array_map(static fn(Message $m): Level => $m->level, $hub->getMessages()->all());
        Assert::same($levels, [Level::Error, Level::Warning, Level::Debug]);
    }

    public function interpolatesPlaceholdersIntoContent(): void
    {
        $hub = $this->hub();
        $hub->channel('app')->info('user {id} ok', ['id' => 42]);
        Assert::same($hub->getMessages()->all()[0]->content, 'user 42 ok');
    }

    public function keepsRawContextIncludingNonPlaceholders(): void
    {
        $hub = $this->hub();
        $hub->channel('app')->error('boom {a}', ['a' => 1, 'b' => 2]);

        $message = $hub->getMessages()->all()[0];
        Assert::same($message->content, 'boom 1');
        Assert::same($message->context, ['a' => 1, 'b' => 2]);
    }

    public function acceptsLevelEnumInLog(): void
    {
        $hub = $this->hub();
        $hub->channel('app')->log(Level::Debug, 'd');
        Assert::same($hub->getMessages()->all()[0]->level, Level::Debug);
    }

    public function unknownStringLevelFallsBackToInfo(): void
    {
        $hub = $this->hub();
        $hub->channel('app')->log('totally-bogus', 'm');
        Assert::same($hub->getMessages()->all()[0]->level, Level::Info);
    }

    private function hub(): MessengerHub
    {
        return new MessengerHub(new SpyDispatcher());
    }
}
