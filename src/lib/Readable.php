<?php

namespace CatPaw\Store;

use function Amp\call;

use Amp\Loop;
use Amp\Promise;
use Generator;
use SplDoublyLinkedList;

class Readable {
    /**
     * @param mixed $value initial value of the store
     * @param  callable(callable):(callable|Promise<callable>|Generator<callable>) $onStart a function that will be executed when the 
     *                                              first subscriber subscribes to the store.
     * 
     *                                              The function should (but it's not required to) return another function, which 
     *                                              will be executed when the last subscriber of the store unsubscribes.
     * @return self
     */
    public static function create(mixed $value, callable $onStart):self {
        return new self($value, $onStart);
    }

    /** @var SplDoublyLinkedList<int, callable> */
    protected SplDoublyLinkedList $callbacks;
    /** @var false|callable */
    private mixed $stop           = false;
    private bool $firstSubscriber = true;

    /**
     * 
     * @param mixed $value
     * @param  callable(callable):(callable|Promise<callable>|Generator<callable>) $onStart
     * @return void
     */
    private function __construct(
        protected mixed $value,
        private mixed $onStart,
    ) {
        $this->callbacks = new SplDoublyLinkedList();
        $this->callbacks->setIteratorMode(SplDoublyLinkedList::IT_MODE_FIFO | SplDoublyLinkedList::IT_MODE_KEEP);
    }

    /**
     * Get the value of the store.
     * @return mixed
     */
    public function get(): mixed {
        return $this->value;
    }

    /**
     * Set the value of the store.
     * @param  mixed $value
     * @return void
     */
    private function set(mixed $value): void {
        $this->value = $value;
        for ($this->callbacks->rewind(); $this->callbacks->valid(); $this->callbacks->next()) {
            $callback = $this->callbacks->current();
            call($callback, $this->value);
        }
    }


    /**
     * Subscribe to this store and get notified of every update.
     * @param  callable(mixed $value) $callback a function that's executed whenever there's an update,
     *                                  it takes 1 parameter, the new value of the store.
     * @return callable():void a function that cancels this subscriptions.
     */
    public function subscribe(callable $callback): callable {
        $this->callbacks->push($callback);

        if ($this->firstSubscriber) {
            $this->firstSubscriber = false;
            //Mount the store and retrieve the stop function for later use
            call(fn () => $this->stop = yield call($this->onStart, fn (mixed $value) => Loop::defer(fn () => $this->set($value))));
            // Loop::defer(fn () => $this->stop = yield call($this->onStart, fn (mixed $value) => $this->set($value)));
        }

        call($callback, $this->value);

        return function() use ($callback):void {
            $this->unsubscribe($callback);
        };
    }

    private function unsubscribe(callable $callback):void {
        for ($this->callbacks->rewind(); $this->callbacks->valid(); $this->callbacks->next()) {
            if ($this->callbacks->current() === $callback) {
                $this->callbacks->offsetUnset($this->callbacks->key());
                if (0 === $this->callbacks->count()) {
                    if ($this->stop) {
                        ($this->stop)();
                    }
                    $this->firstSubscriber = true;
                }
                return;
            }
        }
    }
}