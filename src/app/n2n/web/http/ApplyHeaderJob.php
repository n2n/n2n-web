<?php

namespace n2n\web\http;

class ApplyHeaderJob implements HeaderJob {
	private $headerStr;
	private $replace;

	private string $name;
	private ?string $value;

	/**
	 *
	 * @param string $headerStr
	 * @param bool $replace
	 */
	public function __construct(string $headerStr, bool $replace = true) {
		if (is_numeric(strpos($headerStr, "\r")) || is_numeric(strpos($headerStr, "\n"))) {
			throw new \InvalidArgumentException('Illegal chars in http header str: ' . $headerStr);
		}

		// @todo maybe throw an illegalargument exception headerStr contains illegal characters.
		$this->headerStr = str_replace(array("\r", "\n"), '', (string) $headerStr);
		$this->replace = (boolean) $replace;
	}

	private function ensureNameValueAnalyzed(): void {
		if (isset($this->name)) {
			return;
		}

		$parts = explode(':', $this->headerStr, 2);
		$this->name = trim($parts[0]);
		$this->value = isset($parts[1]) ? trim($parts[1]) : null;
	}

	function getName(): string {
		$this->ensureNameValueAnalyzed();
		return $this->name;
	}

	function getValue(): ?string {
		$this->ensureNameValueAnalyzed();
		return $this->value;
	}

	function isRemove(): bool {
		return false;
	}

	/**
	 *
	 * @return string
	 */
	public function getHeaderStr() {
		return $this->headerStr;
	}

	/**
	 *
	 * @return bool
	 */
	public function isReplace() {
		return $this->replace;
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		return $this->headerStr;
	}

	function flush(): void {
		header($this->getHeaderStr(), $this->isReplace());
	}
}