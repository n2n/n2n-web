<?php

namespace n2n\web\http\controller;

use PHPUnit\Framework\TestCase;
use n2n\web\http\mock\StringBackedEnumMock;
use n2n\web\http\StatusException;
use n2n\web\http\mock\PureEnumMock;
use DateTimeImmutable;
use n2n\web\http\mock\MockBoolValueObject;
use n2n\web\http\mock\MockIntValueObject;
use n2n\spec\valobj\err\IllegalValueException;
use n2n\web\http\mock\ScalarValueObjectEntityMock;
use n2n\web\http\mock\MockFloatValueObject;
use n2n\web\http\mock\MockStringValueObject;
use n2n\web\http\mock\MockSecretValueObject;
use InvalidArgumentException;

class ParamTest extends TestCase {
	protected function setUp(): void {
		$this->ScalarValueObject();
	}

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

	private function ScalarValueObject(): void {
		$vo = new ScalarValueObjectEntityMock();
	}

	/**
	 * @throws StatusException
	 */
	function testToBoolValueObject() {
		$this->assertEquals(new MockBoolValueObject(true),
				(new ParamGet('1')->toBoolValueObject(MockBoolValueObject::class)));
		$this->assertEquals(new MockBoolValueObject(false),
				(new ParamGet('0')->toBoolValueObject(MockBoolValueObject::class)));
		$this->expectException(StatusException::class);
		(new ParamGet('10')->toBoolValueObject(MockBoolValueObject::class));
	}

	/**
	 * @throws StatusException
	 * @throws IllegalValueException
	 */
	function testToIntValueObject() {
		$this->assertEquals(new MockIntValueObject(10),
				(new ParamGet('10')->toIntValueObject(MockIntValueObject::class)));
		$this->expectException(StatusException::class);
		(new ParamGet('10.5')->toIntValueObject(MockIntValueObject::class));
	}

	/**
	 * @throws StatusException
	 * @throws IllegalValueException
	 */
	function testToFloatValueObject() {
		$this->assertEquals(new MockFloatValueObject(10.5),
				(new ParamGet('10.5')->toFloatValueObject(MockFloatValueObject::class)));
		$this->expectException(StatusException::class);
		(new ParamGet('10.5.5')->toFloatValueObject(MockFloatValueObject::class));
	}

	/**
	 * @throws StatusException
	 */
	function testToStringValueObject() {
		$this->assertEquals(new MockStringValueObject('blubb'),
				(new ParamGet('blubb')->toStringValueObject(MockStringValueObject::class)));
		$this->expectException(StatusException::class);
		$this->expectExceptionMessage('Value type not allowed with constraints');
		(new ParamGet(['aa'])->toStringValueObject(MockStringValueObject::class));
	}

	/**
	 * @throws StatusException
	 */
	function testToValueObjectExpectExceptionBecauseMissingImplementation() {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Class must implement');
		(new ParamGet('blubb')->toStringValueObject(MockIntValueObject::class));
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
		(new ParamGet('blubb')->toStringValueObject(MockSecretValueObject::class));
	}

}

