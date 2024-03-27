<?php

namespace Tests;

use function Amp\delay;
use Amp\Loop;
use function CatPaw\Store\readable;
use function CatPaw\Store\writable;

use PHPUnit\Framework\TestCase;

class ReadableTest extends TestCase {
    public function testCreationAnSubscribe() {
        Loop::run(function() {
            $store = readable("hello", function($set) {
                yield delay(500);
                $set("hello world");
                return function() { };
            });
    
            
            $unsubscribe = $store->subscribe(fn ($value) => $this->assertEquals("hello", $value));
            $unsubscribe();
    
            yield delay(600);
            $unsubscribe = $store->subscribe(fn ($value) => $this->assertEquals("hello world", $value));
            $unsubscribe();
        });
    }

    public function testMultipleSubscribersWithDelay() {
        Loop::run(function() {
            $store = readable("default", function($set) {
                //you can execute async code here
                $set("hello world");
                return function() {
                    echo "All subscribers have unsubscribed\n";
                };
            });

            $value1 = '';
            $value2 = '';
            $value3 = '';

            $unsubscribe1 = $store->subscribe(function($value) use (&$value1) {
                if ('default' === $value) {
                    return;
                }
                //you can execute async code here
                $value1 = $value;
                echo "new value received: $value".PHP_EOL;
            });

            $unsubscribe2 = $store->subscribe(function($value) use (&$value2) {
                if ('default' === $value) {
                    return;
                }
                //you can execute async code here
                $value2 = $value;
                echo "new value received: $value".PHP_EOL;
            });

            $unsubscribe3 = $store->subscribe(function($value) use (&$value3) {
                if ('default' === $value) {
                    return;
                }
                //you can execute async code here
                $value3 = $value;
                echo "new value received: $value".PHP_EOL;
            });
        
        
            delay(500)->onResolve(function() use (
                $unsubscribe1,
                $unsubscribe2,
                $unsubscribe3,
                &$value1,
                &$value2,
                &$value3,
            ) {
                $unsubscribe1();
                $unsubscribe2();
                $unsubscribe3();

                $this->assertEquals("hello world", $value1);
                $this->assertEquals("hello world", $value2);
                $this->assertEquals("hello world", $value3);
            });
        });
    }

    public function testMultipleSubscribers() {
        Loop::run(function() {
            $unsubscribers = [];

            $value1 = '';
            $value2 = '';
            $value3 = '';
    
            $counter = writable(0);

            $unsubscribeAll = function() use (&$unsubscribers) {
                foreach ($unsubscribers as $unsubscribe) {
                    $unsubscribe();
                }
            };
    
            $store = readable("default", function($set) use (
                &$value1,
                &$value2,
                &$value3,
            ) {
                //you can execute async code here
                $set("hello world");
                return function() use (
                    &$value1,
                    &$value2,
                    &$value3,
                ) {
                    $this->assertEquals("hello world", $value1);
                    $this->assertEquals("hello world", $value2);
                    $this->assertEquals("hello world", $value3);
    
                    echo "All subscribers have unsubscribed\n";
                };
            });
    
    
            $unsubscribers[] = $store->subscribe(function($value) use ($counter, &$value1) {
                //you can execute async code here
                echo "new value received: $value".PHP_EOL;
                $value1 = $value;
                $counter->set($counter->get() + 1);
            });
    
            $unsubscribers[] = $store->subscribe(function($value) use ($counter, &$value2) {
                //you can execute async code here
                echo "new value received: $value".PHP_EOL;
                $value2 = $value;
                $counter->set($counter->get() + 1);
            });
    
            $unsubscribers[] = $store->subscribe(function($value) use ($counter, &$value3) {
                //you can execute async code here
                echo "new value received: $value".PHP_EOL;
                $value3 = $value;
                $counter->set($counter->get() + 1);
            });

            $counter->subscribe(fn ($counter) => $counter >= 6?$unsubscribeAll():false);
        });
    }
}