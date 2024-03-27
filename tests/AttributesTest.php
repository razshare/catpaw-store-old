<?php

namespace Tests;

use Amp\Loop;
use CatPaw\Store\Attributes\Store;
use CatPaw\Store\Writable;
use CatPaw\Utilities\Container;
use PHPUnit\Framework\TestCase;

class AttributesTest extends TestCase {
    public function testSimpleStuff() {
        Loop::run(function() {
            yield Container::run(function(
                #[Store("test")] Writable $handler1,
                #[Store("test")] Writable $handler2,
            ) {
                $handler1->set("test");
                $this->assertEquals("test", $handler2->get());
            });
        });
    }
}