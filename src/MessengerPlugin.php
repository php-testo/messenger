<?php

declare(strict_types=1);

namespace Testo\Messenger;

use Internal\Container\Container;
use Psr\EventDispatcher\EventDispatcherInterface;
use Testo\Common\EventListenerCollector;
use Testo\Common\PluginConfigurator;
use Testo\Event\TestSuite\TestSuiteFinished;
use Testo\Event\TestSuite\TestSuiteStarting;
use Testo\Messenger;
use Testo\Messenger\Internal\MessengerHub;
use Testo\Messenger\Internal\Pipeline\OutputInterceptor;
use Testo\Pipeline\InterceptorCollector;

/**
 * Plugin that captures test output and exposes a channel-based messaging hub.
 *
 * Registers a single global {@see Messenger} in the container (so other plugins can inject it),
 * an {@see OutputInterceptor} interceptor that forks the messenger per test and attaches the recorded
 * messages to each {@see \Testo\Core\Context\TestResult}, and a process-wide output buffer that
 * funnels native output into the `stdout` channel.
 *
 * The output buffer is opened on {@see TestSuiteStarting} and closed on {@see TestSuiteFinished}
 * rather than per test: with a chunk size of `1` every write is flushed immediately, so each
 * chunk is attributed to whichever scope ({@see Messenger::scope()}) is active at the moment it
 * is produced — i.e. the running test. This keeps buffer management out of the hot per-test path.
 *
 * @see Messenger
 * @api
 */
final readonly class MessengerPlugin implements PluginConfigurator
{
    /**
     * Channel name for captured native output.
     *
     * @var non-empty-string
     */
    public const CHANNEL_STDOUT = 'stdout';

    #[\Override]
    public function configure(Container $container): void
    {
        $messenger = new MessengerHub($container->get(EventDispatcherInterface::class));
        $container->set($messenger, Messenger::class, destroy: true);

        $container->get(InterceptorCollector::class)->addInterceptor(new OutputInterceptor($messenger));

        $this->captureOutput($container->get(EventListenerCollector::class), $messenger);
    }

    /**
     * Bracket each suite's execution with a process-wide output buffer routed into `stdout`.
     */
    private function captureOutput(EventListenerCollector $events, Messenger $messenger): void
    {
        $stdout = $messenger->channel(self::CHANNEL_STDOUT);

        # Output buffers are a process-wide stack; remember the level we started at so the
        # matching close drains exactly our buffer (and anything a test left open on top of it).
        $baseLevel = 0;

        $events->addListener(
            TestSuiteStarting::class,
            static function () use ($stdout, &$baseLevel): void {
                $baseLevel = \ob_get_level();
                \ob_start(static function (string $buffer) use ($stdout): string {
                    $buffer === '' or $stdout->write($buffer);
                    return '';
                }, 1);
            },
        );

        $events->addListener(
            TestSuiteFinished::class,
            static function () use (&$baseLevel): void {
                while (\ob_get_level() > $baseLevel) {
                    \ob_end_flush();
                }
            },
        );
    }
}
