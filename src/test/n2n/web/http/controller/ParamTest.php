<?php

namespace n2n\web\http\controller;

use PHPUnit\Framework\TestCase;
use n2n\web\http\mock\StringBackedEnumMock;
use n2n\web\http\StatusException;
use n2n\web\http\mock\PureEnumMock;
use n2n\util\DateUtils;
use DateTimeImmutable;

class ParamTest extends TestCase {

	/**
	 * @throws StatusException
	 */
	function testToBackedEnum() {
		$this->assertEquals(StringBackedEnumMock::VALUE1,
				(new ParamGet('value-1'))->toEnum(StringBackedEnumMock::cases()));

		$this->expectException(StatusException::class);
		(new ParamGet('VALUE1'))->toEnum(StringBackedEnumMock::cases());

	}

	/**
	 * @throws StatusException
	 */
	function testToPseudoEnum() {
		$this->assertEquals('v1', (new ParamGet('v1'))->toEnum(['v1', 'v2']));

		$this->expectException(StatusException::class);
		(new ParamGet('v3'))->toEnum(['v1', 'v2']);
	}

	/**
	 * @throws StatusException
	 */
	function testToPureEnum() {
		$this->assertEquals(PureEnumMock::CASE1,
				(new ParamGet('CASE1'))->toEnum(PureEnumMock::cases()));

		$this->expectException(StatusException::class);
		(new ParamGet('CASE3'))->toEnum(PureEnumMock::cases());

	}

	/**
	 * @throws StatusException
	 */
	function testParseDateTimeImmutable() {
		$this->assertEquals(new DateTimeImmutable('2024-11-17 23:00:00'),
				(new ParamGet('2024-11-17 23:00:00'))->parseDateTimeImmutable());

		$this->expectException(StatusException::class);
		(new ParamGet('2024-11-17'))->parseDateTimeImmutable();

	}

}

