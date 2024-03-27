<?php

namespace {

	use function Amp\delay;
	use function CatPaw\Store\readable;
	use function CatPaw\Store\writable;

	function main() {
		$time = readable(time(), function($set) {
			ticktock(5, fn() => $set(time()));
		});
		$time->subscribe(fn($time) => print("the time is $time\n"));
	}

	function ticktock(int $iterations, callable $callback) {
		delay(1000)->onResolve(function() use ($iterations, $callback) {
			$callback();
			$iterations--;
			if($iterations > 0)
				ticktock($iterations, $callback);
		});
	}
}