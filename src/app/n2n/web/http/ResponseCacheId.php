<?php

namespace n2n\web\http;

use n2n\util\uri\Path;

class ResponseCacheId {

	function __construct(private int $method, private string $hostName, private Path $path,
			private ?array $queryParams) {
	}

	public function getMethod(): int {
		return $this->method;
	}

	public function getHostName(): string {
		return $this->hostName;
	}

	public function getPath(): Path {
		return $this->path;
	}

	public function getQueryParams(): ?array {
		return $this->queryParams;
	}

	static function createFromRequest(Request $request, bool $queryParamsIncluded): ResponseCacheId {
		return new ResponseCacheId($request->getMethod(), $request->getHostName(), $request->getPath(),
				($queryParamsIncluded ? $request->getQuery()->toArray() : null));
	}



}