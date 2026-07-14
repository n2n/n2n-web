<?php

namespace n2n\web\http\controller\impl;

use n2n\spec\valobj\err\IllegalValueException;
use PHPUnit\Framework\TestCase;
use n2n\web\http\StatusException;
use n2n\web\http\controller\impl\mock\StringBackedEnumMock;
use n2n\web\http\controller\impl\mock\PureEnumMock;
use n2n\web\http\controller\impl\mock\FloatValueObjectMock;
use n2n\web\http\controller\impl\mock\IntValueObjectMock;
use n2n\web\http\controller\impl\mock\BoolValueObjectMock;
use n2n\web\http\controller\impl\mock\StringValueObjectMock;
use n2n\test\case\N2nTestCaseTrait;

class HttpDataTest extends TestCase {
	use N2nTestCaseTrait;
	/**
	 * @throws StatusException
	 */
	function testClean() {
		$dataMap = new HttpData(['key1' => ['skey1' => " \t st\x00ri\r\nng \r\n \n"]]);

		$dataMap->cleanStrings(['key1/skey1']);

		$this->assertEquals("stri ng", $dataMap->reqString('key1/skey1'));
	}

	/**
	 * @throws StatusException
	 */
	function testClean2() {
		$dataMap = new HttpData(['key1' => ['skey1' => " \t st\x00ri\r\nng \r\n \n"]]);

		$dataMap->cleanStrings(['key1/skey1'], false);

		$this->assertEquals(" \t stri\r\nng \r\n \n", $dataMap->reqString('key1/skey1'));
	}

	/**
	 * @throws StatusException
	 */
	function testReqEnum() {
		$dataMap = new HttpData(['key1' => 'value-1', 'key2' => 'CASE2', 'key3' => StringBackedEnumMock::VALUE1]);

		$this->assertEquals(StringBackedEnumMock::VALUE1,
				$dataMap->reqEnum('key1', StringBackedEnumMock::cases()));

		$this->assertEquals(PureEnumMock::CASE2,
				$dataMap->reqEnum('key2', PureEnumMock::cases()));

		$this->expectException(StatusException::class);
		$dataMap->reqEnum('key3', PureEnumMock::cases());
	}

	/**
	 * @throws StatusException
	 */
	function testReqEnumMissingAttributes() {
		$dataMap = new HttpData(['key1' => 'value-1', 'key2' => 'CASE2']);

		$this->assertEquals(StringBackedEnumMock::VALUE1,
				$dataMap->reqEnum('key1', StringBackedEnumMock::cases()));

		$this->assertEquals(PureEnumMock::CASE2,
				$dataMap->reqEnum('key2', PureEnumMock::cases()));

		$this->expectException(StatusException::class);
		$dataMap->reqEnum('key3', PureEnumMock::cases());
	}

	/**
	 * @throws StatusException
	 */
	function testOptEnum() {
		$dataMap = new HttpData(['key1' => 'value-1', 'key2' => 'CASE2']);

		$this->assertEquals(StringBackedEnumMock::VALUE1,
				$dataMap->optEnum('key1', StringBackedEnumMock::cases()));

		$this->assertEquals(PureEnumMock::CASE2,
				$dataMap->optEnum('key2', PureEnumMock::cases()));

		$dataMap->optEnum('key3', PureEnumMock::cases());
	}

	/**
	 * @throws StatusException
	 */
	function testReqScalar() {
		$dataMap = new HttpData(['key1' => 'value-1', 'key2' => StringBackedEnumMock::VALUE1]);
		$this->assertEquals('value-1',
				$dataMap->reqScalar('key1', false));

		$this->expectException(StatusException::class);
		$dataMap->reqScalar('key2', false);
	}

	/**
	 * @throws StatusException
	 */
	function testReqScalarMissingAttributes() {
		$dataMap = new HttpData(['key1' => 'value-1']);
		$this->assertEquals('value-1',
				$dataMap->reqScalar('key1', false));

		$this->expectException(StatusException::class);
		$dataMap->reqScalar('key3', false);
	}

	/**
	 * @throws StatusException
	 */
	function testOptScalar() {
		$dataMap = new HttpData(['key1' => 'value-1', 'key2' => StringBackedEnumMock::VALUE1]);
		$this->assertEquals('value-1',
				$dataMap->optScalar('key1', false));

		$dataMap->optScalar('key3', false);

		$this->expectException(StatusException::class);
		$dataMap->optScalar('key2', false);
	}

	/**
	 * @throws StatusException
	 */
	function testReqBool() {
		$dataMap = new HttpData(['key1' => true, 'key2' => StringBackedEnumMock::VALUE1]);
		$this->assertSame(true,
				$dataMap->reqBool('key1', false, false));

		$this->expectException(StatusException::class);
		$dataMap->reqBool('key2', false, false);
	}

	/**
	 * @throws StatusException
	 */
	function testReqBoolMissingAttributes() {
		$dataMap = new HttpData(['key1' => true]);
		$this->assertSame(true,
				$dataMap->reqBool('key1', false, false));

		$this->expectException(StatusException::class);
		$dataMap->reqBool('key3', false, false);
	}

	/**
	 * @throws StatusException
	 */
	function testOptBool() {
		$dataMap = new HttpData(['key1' => false, 'key2' => StringBackedEnumMock::VALUE1]);
		$this->assertSame(false,
				$dataMap->optBool('key1', false, true, false));

		$dataMap->optBool('key3', false, true, false);

		$this->expectException(StatusException::class);
		$dataMap->optBool('key2', false, true, false);
	}

	/**
	 * @throws StatusException
	 */
	function testReqNumeric() {
		$dataMap = new HttpData(['key1' => 10, 'key2' => StringBackedEnumMock::VALUE1]);
		$this->assertSame(10,
				$dataMap->reqNumeric('key1', false));

		$this->expectException(StatusException::class);
		$dataMap->reqNumeric('key2', false);
	}

	/**
	 * @throws StatusException
	 */
	function testReqNumericMissingAttributes() {
		$dataMap = new HttpData(['key1' => '10']);
		$this->assertSame('10',
				$dataMap->reqNumeric('key1', false));

		$this->expectException(StatusException::class);
		$dataMap->reqNumeric('key3', false);
	}

	/**
	 * @throws StatusException
	 */
	function testOptNumeric() {
		$dataMap = new HttpData(['key1' => 10.01, 'key2' => StringBackedEnumMock::VALUE1]);
		$this->assertSame(10.01,
				$dataMap->optNumeric('key1', false, true));

		$dataMap->optNumeric('key3', false, true);

		$this->expectException(StatusException::class);
		$dataMap->optNumeric('key2', false, true);
	}

	/**
	 * @throws StatusException
	 */
	function testReqInt() {
		$dataMap = new HttpData(['key1' => 10, 'key2' => StringBackedEnumMock::VALUE1]);
		$this->assertSame(10,
				$dataMap->reqInt('key1', false));

		$this->expectException(StatusException::class);
		$dataMap->reqInt('key2', false, false);
	}

	/**
	 * @throws StatusException
	 */
	function testReqIntMissingAttributes() {
		$dataMap = new HttpData(['key1' => 10]);
		$this->assertSame(10,
				$dataMap->reqInt('key1', false));

		$this->expectException(StatusException::class);
		$dataMap->reqInt('key3', false);
	}

	/**
	 * @throws StatusException
	 */
	function testOptInt() {
		$dataMap = new HttpData(['key1' => 10.01, 'key2' => StringBackedEnumMock::VALUE1]);
		$this->assertSame(10,
				$dataMap->optInt('key1', false, true));

		$dataMap->optInt('key3', false, true);

		$this->expectException(StatusException::class);
		$dataMap->optInt('key2', false, true);
	}

	/**
	 * @throws StatusException
	 */
	function testReqArray() {
		$dataMap = new HttpData(['key1' => ['val1' => 10], 'key2' => StringBackedEnumMock::VALUE1]);
		$this->assertSame(['val1' => 10],
				$dataMap->reqArray('key1', 'int'));

		$this->expectException(StatusException::class);
		$dataMap->reqArray('key2', 'int', false);
	}

	/**
	 * @throws StatusException
	 */
	function testReqArrayMissingAttributes() {
		$dataMap = new HttpData(['key1' => [10]]);
		$this->assertSame([10],
				$dataMap->reqArray('key1', 'int'));

		$this->expectException(StatusException::class);
		$dataMap->reqArray('key3', 'int');
	}

	/**
	 * @throws StatusException
	 */
	function testOptArray() {
		$dataMap = new HttpData(['key1' => [10], 'key2' => StringBackedEnumMock::VALUE1]);
		$this->assertSame([10],
				$dataMap->optArray('key1', 'int', [2]));

		$this->assertSame([2], $dataMap->optArray('key3', 'int', [2]));

		$this->expectException(StatusException::class);
		$dataMap->optArray('key2', 'int', []);
	}

	/**
	 * @throws StatusException
	 */
	function testReqScalarArray() {
		$dataMap = new HttpData(['key1' => [10], 'key2' => StringBackedEnumMock::VALUE1]);
		$this->assertSame([10],
				$dataMap->reqScalarArray('key1', false));

		$this->expectException(StatusException::class);
		$dataMap->reqScalarArray('key2', false, false);
	}

	/**
	 * @throws StatusException
	 */
	function testReqScalarArrayMissingAttributes() {
		$dataMap = new HttpData(['key1' => [10]]);
		$this->assertSame([10],
				$dataMap->reqScalarArray('key1', false));

		$this->expectException(StatusException::class);
		$dataMap->reqScalarArray('key3', false);
	}

	/**
	 * @throws StatusException
	 */
	function testOptScalarArray() {
		$dataMap = new HttpData(['key1' => [10], 'key2' => StringBackedEnumMock::VALUE1]);
		$this->assertSame([10],
				$dataMap->optScalarArray('key1', [2], true));

		$this->assertSame([2], $dataMap->optScalarArray('key3', [2], true));

		$this->expectException(StatusException::class);
		$dataMap->optScalarArray('key2', [2], true);
	}

	/**
	 * @throws StatusException
	 * @throws IllegalValueException
	 */
	function testReqStringValueObject() {
		$dataMap = new HttpData(['key1' => 'a10' ,'key2' => new IntValueObjectMock(10)]);
		$this->assertTypeSafeEquals(new StringValueObjectMock('a10'),
				$dataMap->reqStringValueObject('key1', StringValueObjectMock::class));


		$this->expectException(StatusException::class);
		$dataMap->reqStringValueObject('key2', StringValueObjectMock::class, false);
	}

	/**
	 * @throws StatusException
	 */
	function testReqStringValueObjectMissingAttributes() {
		$dataMap = new HttpData(['key1' => new StringValueObjectMock('10')]);
		$this->assertSame('10',
				$dataMap->reqStringValueObject('key1', StringValueObjectMock::class)->toScalar());

		$this->expectException(StatusException::class);
		$dataMap->reqStringValueObject('key3', StringValueObjectMock::class);
	}

	/**
	 * @throws StatusException
	 * @throws IllegalValueException
	 */
	function testOptStringValueObject() {
		$dataMap = new HttpData(['key1' => 10.01, 'key2' => new IntValueObjectMock(10)]);
		$this->assertSame('10.01',
				$dataMap->optStringValueObject('key1', StringValueObjectMock::class, null)->toScalar());

		$dataMap->optStringValueObject('key3', StringValueObjectMock::class, null );

		$this->expectException(StatusException::class);
		$dataMap->optStringValueObject('key2', StringValueObjectMock::class, null);
	}


	/**
	 * @throws StatusException
	 * @throws IllegalValueException
	 */
	function testReqIntValueObject() {
		$dataMap = new HttpData(['key1' => 10 ,'key2' => StringBackedEnumMock::VALUE1]);
		$this->assertTypeSafeEquals(new IntValueObjectMock(10),
				$dataMap->reqIntValueObject('key1', IntValueObjectMock::class));

		$this->expectException(StatusException::class);
		$dataMap->reqIntValueObject('key2', IntValueObjectMock::class, false);
	}

	/**
	 * @throws StatusException
	 */
	function testReqIntValueObjectMissingAttributes() {
		$dataMap = new HttpData(['key1' => new IntValueObjectMock('10')]);
		$this->assertSame(10,
				$dataMap->reqIntValueObject('key1', IntValueObjectMock::class)->toScalar());

		$this->expectException(StatusException::class);
		$dataMap->reqIntValueObject('key3', IntValueObjectMock::class);
	}

	/**
	 * @throws StatusException
	 * @throws IllegalValueException
	 */
	function testOptIntValueObject() {
		$dataMap = new HttpData(['key1' => 10, 'key2' => StringBackedEnumMock::VALUE1]);
		$this->assertSame(10,
				$dataMap->optIntValueObject('key1', IntValueObjectMock::class, null)->toScalar());

		$dataMap->optIntValueObject('key3', IntValueObjectMock::class, null );

		$this->expectException(StatusException::class);
		$dataMap->optIntValueObject('key2', IntValueObjectMock::class, null);
	}

	/**
	 * @throws StatusException
	 * @throws IllegalValueException
	 */
	function testReqFloatValueObject() {
		$dataMap = new HttpData(['key1' => 10.01 ,'key2' => StringBackedEnumMock::VALUE1]);
		$this->assertTypeSafeEquals(new FloatValueObjectMock(10.01),
				$dataMap->reqFloatValueObject('key1', FloatValueObjectMock::class));

		$this->expectException(StatusException::class);
		$dataMap->reqFloatValueObject('key2', FloatValueObjectMock::class, false);
	}

	/**
	 * @throws StatusException
	 * @throws IllegalValueException
	 */
	function testReqFloatValueObjectMissingAttributes() {
		$dataMap = new HttpData(['key1' => new FloatValueObjectMock(10.01)]);
		$this->assertSame(10.01,
				$dataMap->reqFloatValueObject('key1', FloatValueObjectMock::class)->toScalar());

		$this->expectException(StatusException::class);
		$dataMap->reqFloatValueObject('key3', FloatValueObjectMock::class);
	}

	/**
	 * @throws StatusException
	 * @throws IllegalValueException
	 */
	function testOptFloatValueObject() {
		$dataMap = new HttpData(['key1' => 10.01, 'key2' => StringBackedEnumMock::VALUE1]);
		$this->assertSame(10.01,
				$dataMap->optFloatValueObject('key1', FloatValueObjectMock::class, null)->toScalar());

		$dataMap->optFloatValueObject('key3', FloatValueObjectMock::class, null );

		$this->expectException(StatusException::class);
		$dataMap->optFloatValueObject('key2', FloatValueObjectMock::class, null);
	}

	/**
	 * @throws StatusException
	 * @throws IllegalValueException
	 */
	function testReqBoolValueObject() {
		$dataMap = new HttpData(['key1' => false ,'key2' => StringBackedEnumMock::VALUE1]);
		$this->assertTypeSafeEquals(new BoolValueObjectMock(false),
				$dataMap->reqBoolValueObject('key1', BoolValueObjectMock::class));

		$this->expectException(StatusException::class);
		$dataMap->reqBoolValueObject('key2', BoolValueObjectMock::class, false);
	}

	/**
	 * @throws StatusException
	 */
	function testReqBoolValueObjectMissingAttributes() {
		$dataMap = new HttpData(['key1' => new BoolValueObjectMock(true)]);
		$this->assertSame(true,
				$dataMap->reqBoolValueObject('key1', BoolValueObjectMock::class)->toScalar());

		$this->expectException(StatusException::class);
		$dataMap->reqBoolValueObject('key3', BoolValueObjectMock::class);
	}

	/**
	 * @throws StatusException
	 * @throws IllegalValueException
	 */
	function testOptBoolValueObject() {
		$dataMap = new HttpData(['key1' => true, 'key2' => StringBackedEnumMock::VALUE1]);
		$this->assertSame(true,
				$dataMap->optBoolValueObject('key1', BoolValueObjectMock::class, null)->toScalar());

		$dataMap->optBoolValueObject('key3', BoolValueObjectMock::class, null );

		$this->expectException(StatusException::class);
		$dataMap->optBoolValueObject('key2', BoolValueObjectMock::class, null);
	}
}