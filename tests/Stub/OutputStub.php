<?php

declare(strict_types=1);

namespace Tests\Messenger\Stub;

use Testo\Assert;
use Testo\Test;

/**
 * Stub case run through the real pipeline (via {@see \Testo\Testing\Traits\TestRunner}) to verify
 * that {@see \Testo\Messenger\Internal\Pipeline\OutputInterceptor} captures native output into the
 * test's {@see \Testo\Core\Context\TestResult}.
 */
final class OutputStub
{
    #[Test]
    public function emitsOutput(): void
    {
        echo 'hello from test';
        Assert::true(true);
    }

    #[Test]
    public function silent(): void {}

    #[Test]
    public function failsAfterOutput(): void
    {
        echo 'before failure';
        Assert::true(false);
    }
}
