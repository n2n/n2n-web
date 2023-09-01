<?php

namespace n2n\web\http\cache;

use n2n\core\container\Transaction;
use n2n\core\container\TransactionalResource;

class CacheActionQueue implements TransactionalResource {
	private ?array $onCommitClosures = null;

	private function reset(): void {
		$this->onCommitClosures = null;
	}

	private function isInTransaction(): bool {
		return $this->onCommitClosures !== null;
	}

	public function registerAction(bool $master, \Closure $closure): void {
		if (!$this->isInTransaction()) {
			$closure();
			return;
		}

		if ($master) {
			$this->onCommitClosures = array();
		}

		$this->onCommitClosures[] = $closure;
	}

	public function beginTransaction(Transaction $transaction): void {
		$this->onCommitClosures = [];
	}

	public function prepareCommit(Transaction $transaction): void {
	}

	public function requestCommit(Transaction $transaction): void {
	}

	public function commit(Transaction $transaction): void {
		while (null !== ($onCommitClosure = array_shift($this->onCommitClosures))) {
			$onCommitClosure();
		}

		$this->reset();
	}

	/**
	 * @param Transaction $transaction
	 */
	public function rollBack(Transaction $transaction): void {
		$this->reset();
	}

	function release(): void {
	}
}