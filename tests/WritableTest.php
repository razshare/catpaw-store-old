<?php

namespace Tests;

use function Amp\delay;

use Amp\Loop;
use function CatPaw\Store\writable;

use PHPUnit\Framework\TestCase;

class WritableTest extends TestCase {
    public function testSet() {
        Loop::run(function() {
            $store = writable("hello");
            $this->assertEquals("hello", $store->get());
            $store->set("hello world");
            $this->assertEquals("hello world", $store->get());
        });
    }

    public function testSubscribe() {
        Loop::run(function() {
            $startTime = time();
            yield delay(1000);
            $store = writable(time());
            $store->subscribe(fn ($now) => $this->assertGreaterThan($startTime, $now));
            $store->set(time());
        });
    }

    public function testUpdate() {
        Loop::run(function() {
            $store = writable(0);
            $store->subscribe(function($value) {
                $this->assertEquals(1, $value);
            });
            $store->update(fn ($value) => ++$value);
        });
    }
}