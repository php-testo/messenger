<?php

declare(strict_types=1);

namespace Tests\Messenger\Feature;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Core\Value\Status;
use Testo\Messenger\Internal\Pipeline\OutputInterceptor;
use Testo\Messenger\MessengerPlugin;
use Testo\Test;
use Testo\Testing\Attribute\TestingSuite;
use Testo\Testing\Traits\TestRunner;
use Tests\Messenger\Stub\OutputStub;

/**
 * End-to-end checks that {@see OutputInterceptor} (active by default) captures a test's native
 * output into its {@see \Testo\Core\Context\TestResult} when the whole testo pipeline runs.
 *
 * Each test runs {@see OutputStub} through {@see TestRunner} and inspects the returned result.
 */
#[Test]
#[Covers(OutputInterceptor::class)]
#[TestingSuite(path: __DIR__ . '/../Stub/OutputStub.php', plugins: [MessengerPlugin::class])]
final class OutputInterceptorTest
{
    public function capturesNativeOutputIntoResultMessages(): void
    {
        $result = TestRunner::runTest([OutputStub::class, 'emitsOutput']);

        Assert::same($result->status, Status::Passed);
        Assert::false($result->messages->isEmpty());
        Assert::same($result->messages->channel('stdout')[0]->content, 'hello from test');
    }

    public function silentTestProducesNoMessages(): void
    {
        $result = TestRunner::runTest([OutputStub::class, 'silent']);

        Assert::true($result->messages->isEmpty());
    }

    public function outputIsAttachedEvenWhenTestFails(): void
    {
        $result = TestRunner::runTest([OutputStub::class, 'failsAfterOutput']);

        Assert::same($result->status, Status::Failed);
        Assert::same($result->messages->channel('stdout')[0]->content, 'before failure');
    }
}
