<?php

namespace n2n\web\http\controller;

use PHPUnit\Framework\TestCase;
use n2n\web\http\mock\MockedStringBackedEnum;
use n2n\web\http\StatusException;
use n2n\web\http\mock\PureEnumMock;
use DateTimeImmutable;
use n2n\spec\valobj\err\IllegalValueException;
use InvalidArgumentException;
use n2n\web\http\controller\mock\MockedBoolValueObject;
use n2n\web\http\controller\mock\MockedIntValueObject;
use n2n\web\http\controller\mock\MockedFloatValueObject;
use n2n\web\http\controller\mock\MockedStringValueObject;
use n2n\web\http\controller\mock\MockedSecretValueObject;

class ParamTest extends TestCase {
	protected function setUp(): void {
	}

	/**
	 * @throws StatusException
	 */
	function testToBackedEnum() {
		$this->assertEquals(MockedStringBackedEnum::VALUE1,
				(new ParamGet('value-1'))->toEnum(MockedStringBackedEnum::cases()));

		$this->expectException(StatusException::class);
		(new ParamGet('VALUE1'))->toEnum(MockedStringBackedEnum::cases());

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

	/**
	 * @throws StatusException
	 */
	function testToBoolValueObject() {
		$this->assertEquals(new MockedBoolValueObject(true),
				(new ParamGet('1')->toBoolValueObject(MockedBoolValueObject::class)));
		$this->assertEquals(new MockedBoolValueObject(false),
				(new ParamGet('0')->toBoolValueObject(MockedBoolValueObject::class)));
		$this->expectException(StatusException::class);
		(new ParamGet('10')->toBoolValueObject(MockedBoolValueObject::class));
	}

	/**
	 * @throws StatusException
	 * @throws IllegalValueException
	 */
	function testToIntValueObject() {
		$this->assertEquals(new MockedIntValueObject(10),
				(new ParamGet('10')->toIntValueObject(MockedIntValueObject::class)));
		$this->expectException(StatusException::class);
		(new ParamGet('10.5')->toIntValueObject(MockedIntValueObject::class));
	}

	/**
	 * @throws StatusException
	 * @throws IllegalValueException
	 */
	function testToFloatValueObject() {
		$this->assertEquals(new MockedFloatValueObject(10.5),
				(new ParamGet('10.5')->toFloatValueObject(MockedFloatValueObject::class)));
		$this->expectException(StatusException::class);
		(new ParamGet('10.5.5')->toFloatValueObject(MockedFloatValueObject::class));
	}

	/**
	 * @throws StatusException
	 */
	function testToStringValueObject() {
		$this->assertEquals(new MockedStringValueObject('blubb'),
				(new ParamGet('blubb')->toStringValueObject(MockedStringValueObject::class)));
		$this->expectException(StatusException::class);
		$this->expectExceptionMessage('Value type not allowed with constraints');
		(new ParamGet(['aa'])->toStringValueObject(MockedStringValueObject::class));
	}

	/**
	 * @throws StatusException
	 */
	function testToValueObjectExpectExceptionBecauseMissingImplementation() {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Class must implement');
		(new ParamGet('blubb')->toStringValueObject(MockedIntValueObject::class));
	}

	/**
	 * @throws StatusException
	 */
	function testToValueObjectExpectExceptionBecauseInvalidClassName() {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid value object class name passed');
		(new ParamGet('blubb')->toStringValueObject('missingClass'));
	}

	/**
	 * @throws StatusException
	 */
	function testToValueObjectExpectExceptionBecauseInvalidClass() {
		$this->expectException(StatusException::class);
		$this->expectExceptionMessage('you need to provide the correct secret value');
		(new ParamGet('blubb')->toStringValueObject(MockedSecretValueObject::class));
	}

}

