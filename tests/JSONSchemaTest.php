<?php
namespace itsoneiota\json;

/**
 * Tests for JSONSchema.
 * @group JSON
 *
 **/
class JSONSchemaTest extends \PHPUnit_Framework_TestCase {

	protected $sut;

	public function setUp() {
		$this->sut = new JSONSchema();
	}

	/**
	 * It should validate the type of the value.
	 * @test
	 */
	public function canValidateType() {
		// No type set.
		$this->assertTrue($this->sut->validate('string'));
		$this->assertTrue($this->sut->validate(1.5));
		$this->assertTrue($this->sut->validate(4));
		$this->assertTrue($this->sut->validate(TRUE));
		$this->assertTrue($this->sut->validate(new \stdClass()));
		$this->assertTrue($this->sut->validate(array()));
		$this->assertTrue($this->sut->validate(NULL));

		$this->sut->setType('any');
		$this->assertTrue($this->sut->validate('string'));
		$this->assertTrue($this->sut->validate(1.5));
		$this->assertTrue($this->sut->validate(4));
		$this->assertTrue($this->sut->validate(TRUE));
		$this->assertTrue($this->sut->validate(new \stdClass()));
		$this->assertTrue($this->sut->validate(array()));
		$this->assertTrue($this->sut->validate(NULL));

		$this->sut->setType('null');
		$this->assertFalse($this->sut->validate('string'));
		$this->assertFalse($this->sut->validate(1.5));
		$this->assertFalse($this->sut->validate(4));
		$this->assertFalse($this->sut->validate(TRUE));
		$this->assertFalse($this->sut->validate(new \stdClass()));
		$this->assertFalse($this->sut->validate(array()));
		$this->assertTrue($this->sut->validate(NULL));

		$this->sut->setType('string');
		$this->assertTrue($this->sut->validate('string'));
		$this->assertFalse($this->sut->validate(1.5));
		$this->assertFalse($this->sut->validate(4));
		$this->assertFalse($this->sut->validate(TRUE));
		$this->assertFalse($this->sut->validate(new \stdClass()));
		$this->assertFalse($this->sut->validate(array()));
		$this->assertFalse($this->sut->validate(NULL));

		$this->sut->setType('integer');
		$this->assertFalse($this->sut->validate('3'));
		$this->assertFalse($this->sut->validate(1.5));
		$this->assertTrue($this->sut->validate(4));
		$this->assertFalse($this->sut->validate(TRUE));
		$this->assertFalse($this->sut->validate(new \stdClass()));
		$this->assertFalse($this->sut->validate(array()));
		$this->assertFalse($this->sut->validate(NULL));

		$this->sut->setType('number');
		$this->assertFalse($this->sut->validate('1.4'));
		$this->assertTrue($this->sut->validate(1.5));
		$this->assertTrue($this->sut->validate(4));
		$this->assertFalse($this->sut->validate(TRUE));
		$this->assertFalse($this->sut->validate(new \stdClass()));
		$this->assertFalse($this->sut->validate(array()));
		$this->assertFalse($this->sut->validate(NULL));

		$this->sut->setType('boolean');
		$this->assertFalse($this->sut->validate('1.4'));
		$this->assertFalse($this->sut->validate('true'));
		$this->assertFalse($this->sut->validate('false'));
		$this->assertFalse($this->sut->validate(1.5));
		$this->assertFalse($this->sut->validate(4));
		$this->assertTrue($this->sut->validate(TRUE));
		$this->assertFalse($this->sut->validate(0)); // Permit 0 and 1 in where boolean is expected.
		$this->assertFalse($this->sut->validate(1)); // Permit 0 and 1 in where boolean is expected.
		$this->assertFalse($this->sut->validate('0')); // Permit '0' and '1' in where boolean is expected.
		$this->assertFalse($this->sut->validate('1')); // Permit '0' and '1' in where boolean is expected.
		$this->assertFalse($this->sut->validate(new \stdClass()));
		$this->assertFalse($this->sut->validate(array()));
		$this->assertFalse($this->sut->validate(NULL));

		$this->sut->setOptions(JSONSchema::STRICT_MODE);

		$this->sut->setType('array');
		$this->assertFalse($this->sut->validate('array'));
		$this->assertFalse($this->sut->validate(1.5));
		$this->assertFalse($this->sut->validate(4));
		$this->assertFalse($this->sut->validate(TRUE));
		$this->assertFalse($this->sut->validate(new \stdClass()));
		$this->assertTrue($this->sut->validate(array()));
		$this->assertFalse($this->sut->validate(NULL));

		$this->sut->setType('object');
		$this->assertFalse($this->sut->validate('object'));
		$this->assertFalse($this->sut->validate(1.5));
		$this->assertFalse($this->sut->validate(4));
		$this->assertFalse($this->sut->validate(TRUE));
		$this->assertTrue($this->sut->validate(new \stdClass()));
		$this->assertFalse($this->sut->validate(array('a'=>123)));
		$this->assertFalse($this->sut->validate(NULL));
	}

	/**
	 * It should validate a union of types, including another schema.
	 * @test
	 */
	public function canValidateTypeUsingNestedSchema() {
		$typeSchema = new JSONSchema();
		$typeSchema->setType('object');

		$this->sut->setType($typeSchema);
		$this->assertFalse($this->sut->validate('object'));
		$this->assertFalse($this->sut->validate(1.5));
		$this->assertFalse($this->sut->validate(4));
		$this->assertFalse($this->sut->validate(TRUE));
		$this->assertTrue($this->sut->validate(new \stdClass()));
		$this->assertFalse($this->sut->validate(array('a'=>123)));
		$this->assertFalse($this->sut->validate(NULL));
	}

	/**
	 * It should validate a union of types.
	 * @test
	 */
	public function canValidateUnionTypes() {
		$this->sut->setType(array('object','null'));
		$this->assertFalse($this->sut->validate('object'));
		$this->assertFalse($this->sut->validate(1.5));
		$this->assertFalse($this->sut->validate(4));
		$this->assertFalse($this->sut->validate(TRUE));
		$this->assertTrue($this->sut->validate(new \stdClass()));
		$this->assertFalse($this->sut->validate(array('a'=>123)));
		$this->assertTrue($this->sut->validate(NULL));
	}

	/**
	 * It should validate a union of types, including another schema.
	 * @test
	 */
	public function canValidateUnionTypesIncludingRefenceSchema() {
		$typeSchema = new JSONSchema();
		$typeSchema->setType('object');

		$this->sut->setType(array('boolean',$typeSchema));
		$this->assertFalse($this->sut->validate('object'));
		$this->assertFalse($this->sut->validate(1.5));
		$this->assertFalse($this->sut->validate(4));
		$this->assertTrue($this->sut->validate(TRUE));
		$this->assertTrue($this->sut->validate(new \stdClass()));
		$this->assertFalse($this->sut->validate(array('a'=>123)));
		$this->assertFalse($this->sut->validate(NULL));
	}

	/**
	 * It should validate a union of types, including another schema.
	 * @test
	 */
	public function canValidateUsingAllOf() {
		$minSchema = new JSONSchema();
		$minSchema->setType('integer');
		$minSchema->setMinimum(3);

		$maxSchema = new JSONSchema();
		$maxSchema->setType('number');
		$maxSchema->setMaximum(4);

		$this->sut->setAllOf(array($minSchema,$maxSchema));

		$this->assertFalse($this->sut->validate('object'));
		$this->assertFalse($this->sut->validate(1.5));
		$this->assertFalse($this->sut->validate(TRUE));
		$this->assertFalse($this->sut->validate(new \stdClass()));
		$this->assertFalse($this->sut->validate(array('a'=>123)));
		$this->assertFalse($this->sut->validate(NULL));

		$this->assertFalse($this->sut->validate(2));
		$this->assertTrue($this->sut->validate(3));
		$this->assertFalse($this->sut->validate(3.5));
		$this->assertTrue($this->sut->validate(4));
		$this->assertFalse($this->sut->validate(5));
	}

	/**
	 * It should validate a union of types, including another schema.
	 * @test
	 */
	public function canValidateUsingAnyOf() {
		$lowerSchema = new JSONSchema();
		$lowerSchema->setType('integer');
		$lowerSchema->setMinimum(2);
		$lowerSchema->setMaximum(3);

		$upperSchema = new JSONSchema();
		$upperSchema->setType('integer');
		$upperSchema->setMinimum(3);
		$upperSchema->setMaximum(4);

		$this->sut->setAnyOf(array($lowerSchema,$upperSchema));

		$this->assertFalse($this->sut->validate('object'));
		$this->assertFalse($this->sut->validate(3.5));
		$this->assertFalse($this->sut->validate(TRUE));
		$this->assertFalse($this->sut->validate(new \stdClass()));
		$this->assertFalse($this->sut->validate(array('a'=>123)));
		$this->assertFalse($this->sut->validate(NULL));

		// Below both.
		$this->assertFalse($this->sut->validate(1));
		$this->assertEquals(1, count($this->sut->getErrors()));
		// Matches lower schema.
		$this->assertTrue($this->sut->validate(2));
		// Matches both.
		$this->assertTrue($this->sut->validate(3));
		// Matches upper.
		$this->assertTrue($this->sut->validate(4));
		// Above both.
		$this->assertFalse($this->sut->validate(5));
	}

	/**
	 * It should return an error from an additionalProperties schema with anyOf.
	 * @test
	 */
	public function canGetErrorsForNestedAdditionalProperties() {
		$lowerSchema = new JSONSchema();
		$lowerSchema->setType('integer');
		$lowerSchema->setMinimum(2);
		$lowerSchema->setMaximum(3);

		$upperSchema = new JSONSchema();
		$upperSchema->setType('integer');
		$upperSchema->setMinimum(3);
		$upperSchema->setMaximum(4);

		$anyOfSchema = new JSONSchema();
		$anyOfSchema->setAnyOf(array($this->sut,$upperSchema));

		$this->sut->setType('object');
		$this->sut->setProperty('foo', $anyOfSchema);

		$value = new \stdClass();
		$value->foo = 5;

		$this->assertFalse($this->sut->validate($value));
		$this->assertEquals(1, count($this->sut->getErrors()));
	}

	/**
	 * It should validate a union of types, including another schema.
	 * @test
	 */
	public function canValidateUsingOneOf() {
		$lowerSchema = new JSONSchema();
		$lowerSchema->setType('integer');
		$lowerSchema->setMinimum(2);
		$lowerSchema->setMaximum(3);

		$upperSchema = new JSONSchema();
		$upperSchema->setType('integer');
		$upperSchema->setMinimum(3);
		$upperSchema->setMaximum(4);

		$this->sut->setOneOf(array($lowerSchema,$upperSchema));

		$this->assertFalse($this->sut->validate('object'));
		$this->assertFalse($this->sut->validate(3.5));
		$this->assertFalse($this->sut->validate(TRUE));
		$this->assertFalse($this->sut->validate(new \stdClass()));
		$this->assertFalse($this->sut->validate(array('a'=>123)));
		$this->assertFalse($this->sut->validate(NULL));

		// Below both.
		$this->assertFalse($this->sut->validate(1));
		// Matches lower schema.
		$this->assertTrue($this->sut->validate(2));

		// Matches both.
		$this->assertFalse($this->sut->validate(3));

		// Matches upper.
		$this->assertTrue($this->sut->validate(4));
		// Above both.
		$this->assertFalse($this->sut->validate(5));
	}

	/**
	 * It should validate a union of types, including another schema.
	 * @test
	 */
	public function canValidateUsingOneOfWithOnlyOneSchema() {
		$lowerSchema = new JSONSchema();
		$lowerSchema->setType('integer');
		$lowerSchema->setMinimum(2);
		$lowerSchema->setMaximum(3);

		$this->sut->setOneOf(array($lowerSchema));

		$this->assertFalse($this->sut->validate('object'));
		$this->assertFalse($this->sut->validate(3.5));
		$this->assertFalse($this->sut->validate(TRUE));
		$this->assertFalse($this->sut->validate(new \stdClass()));
		$this->assertFalse($this->sut->validate(array('a'=>123)));
		$this->assertFalse($this->sut->validate(NULL));

		$this->assertFalse($this->sut->validate(1));
		$this->assertTrue($this->sut->validate(2));
		$this->assertTrue($this->sut->validate(3));
		$this->assertFalse($this->sut->validate(4));
	}

	/**
	 * It should validate object properties using schemata.
	 * @test
	 */
	public function canValidateObjectProperties() {
		$value = new \stdClass();
		$value->name = 'Ross';
		$value->age = 30;
		$value->height = 193;

		$nameSchema = $this->getMockBuilder('\itsoneiota\json\JSONSchema')->disableOriginalConstructor()->getMock();
		$ageSchema = $this->getMockBuilder('\itsoneiota\json\JSONSchema')->disableOriginalConstructor()->getMock();
		$heightSchema = $this->getMockBuilder('\itsoneiota\json\JSONSchema')->disableOriginalConstructor()->getMock();

		$nameSchema->expects($this->exactly(2))->method('validate')->with($this->equalTo('Ross'))->will($this->returnValue(TRUE));
		$ageSchema->expects($this->exactly(2))->method('validate')->with($this->equalTo(30))->will($this->returnValue(TRUE));
		$heightSchema->expects($this->once())->method('validate')->with($this->equalTo(193))->will($this->returnValue(FALSE));
		$heightSchema->expects($this->any())->method('getErrors')->will($this->returnValue(array()));

		$this->sut->setType('object');
		$this->sut->setProperty('name', $nameSchema);
		$this->sut->setProperty('age', $ageSchema);
		$this->assertTrue($this->sut->validate($value));

		$this->sut->setProperty('height', $heightSchema);
		$this->assertFalse($this->sut->validate($value));
	}

	/**
	 * It should validate object properties, with additional properties allowed.
	 * @test
	 */
	public function canValidateObjectPropertiesAllowingAdditionalProperties() {
		$value = new \stdClass();
		$value->name = 'Ross';
		$value->age = 30;
		$value->height = 193;
		$value->weight = 95;

		$nameSchema = new JSONSchema();
		$ageSchema = new JSONSchema();
		$heightSchema = new JSONSchema();

		$nameSchema->setType('string');
		$ageSchema->setType('integer');
		$heightSchema->setType('integer');

		$this->sut->setType('object');
		$this->sut->setProperty('name', $nameSchema);
		$this->sut->setProperty('age', $ageSchema);
		$this->sut->setProperty('height', $heightSchema);
		$this->assertTrue($this->sut->validate($value));
	}

	/**
	 * It should validate object properties, with additional properties disallowed.
	 * @test
	 */
	public function canValidateObjectPropertiesDisallowingAdditionalProperties() {
		$value = new \stdClass();
		$value->name = 'Ross';
		$value->age = 30;
		$value->height = 193;

		$nameSchema = new JSONSchema();
		$ageSchema = new JSONSchema();
		$heightSchema = new JSONSchema();

		$nameSchema->setType('string');
		$ageSchema->setType('integer');
		$heightSchema->setType('integer');

		$this->sut->setType('object');
		$this->sut->setProperty('name', $nameSchema);
		$this->sut->setProperty('age', $ageSchema);
		$this->sut->setProperty('height', $heightSchema);
		$this->sut->setAdditionalProperties(FALSE);
		$this->assertTrue($this->sut->validate($value));

		$value->weight = 95;
		$this->assertFalse($this->sut->validate($value));
	}

	/**
	 * It should validate object properties, with additional properties constrained.
	 * @test
	 */
	public function canValidateObjectPropertiesConstrainingAdditionalProperties() {
		$value = new \stdClass();
		$value->name = 'Ross';
		$value->age = 30;
		$value->height = 193;
		$value->weight = 95;

		$nameSchema = new JSONSchema();
		$ageSchema = new JSONSchema();
		$heightSchema = new JSONSchema();

		$nameSchema->setType('string');
		$ageSchema->setType('integer');
		$heightSchema->setType('integer');

		$additionalPropertySchema = new JSONSchema();
		$additionalPropertySchema->setType('integer');

		$this->sut->setType('object');
		$this->sut->setProperty('name', $nameSchema);
		$this->sut->setProperty('age', $ageSchema);
		$this->sut->setProperty('height', $heightSchema);
		$this->sut->setAdditionalProperties($additionalPropertySchema);
		$this->assertTrue($this->sut->validate($value));

		$value->email = 'ross@itsoneiota.co.uk';
		$this->assertFalse($this->sut->validate($value));
	}

	/**
	 * It should fail an object missing a required property.
	 * @test
	 */
	public function canFailObjectMissingARequiredProperty() {
		$value = new \stdClass();
		$value->name = 'Ross';

		$nameSchema = new JSONSchema();
		$nameSchema->setType('string');

		$ageSchema = new JSONSchema();
		$ageSchema->setType('integer');

		$this->sut->setProperty('name',$nameSchema);
		$this->sut->setProperty('age',$ageSchema);
		$this->sut->setRequired(['name', 'age']);

		$this->assertFalse($this->sut->validate($value));

		$value->age = 30;

		$this->assertTrue($this->sut->validate($value));
	}

	/**
	 * It should fail an object missing a required property.
	 * Required is an array in the draft 04 spec.
	 *
	 * @test
	 */
	public function canFailObjectMissingARequiredPropertyUsingRequiredArray() {
		$value = new \stdClass();
		$value->name = 'Ross';

		$this->sut->setRequired(array('age','height'));

		$this->assertFalse($this->sut->validate($value));

		$value->age = 30;

		$this->assertFalse($this->sut->validate($value));

		$value->height = 193;

		$this->assertTrue($this->sut->validate($value));
	}

	/**
	 * It should validate array length against minItems and maxItems.
	 * @test
	 */
	public function canValidateArrayLength() {
		$three= array('1','2','3');
		$four = array('1','2','3','4');
		$five = array('1','2','3','4','5');
		$six = array('1','2','3','4','5','6');

		$itemSchema = new JSONSchema();
		$itemSchema->setType('string');

		$this->sut->setType('array');
		$this->sut->setItems($itemSchema);
		$this->sut->setMinItems(4);
		$this->sut->setMaxItems(5);
		$this->assertFalse($this->sut->validate($three));
		$this->assertTrue($this->sut->validate($four));
		$this->assertTrue($this->sut->validate($five));
		$this->assertFalse($this->sut->validate($six));
	}

	/**
	 * It should validate an array disallowing duplicate values.
	 * @test
	 */
	public function canValidateArrayDuplicateValues() {
		$a = new \stdClass();
		$a->foo = 'bar';
		$b = new \stdClass();
		$b->foo = 'bar';
		$unique= array('1',$a,'3');
		$duplicateStrings = array('1','1',$a);
		$duplicateObjects = array('1',$a,$b,'3');
		$nullAndEmptyStringAreNotTheSame = array('',NULL,$a);

		$this->sut->setType('array');
		$this->sut->setUniqueItems(TRUE);
		$this->assertTrue($this->sut->validate($unique));
		$this->assertFalse($this->sut->validate($duplicateStrings));
		$this->assertFalse($this->sut->validate($duplicateObjects));
		$this->assertTrue($this->sut->validate($nullAndEmptyStringAreNotTheSame));
	}

	/**
	 * It should validate array items using schemata.
	 * @test
	 */
	public function canValidateArrayItemsWithGoodArray() {
		$goodArray = array('1','2','3','4','5');

		$itemSchema = $this->getMockBuilder('\itsoneiota\json\JSONSchema')->disableOriginalConstructor()->getMock();
		$itemSchema->expects($this->at(0))->method('validate')->with($this->equalTo('1'))->will($this->returnValue(TRUE));
		$itemSchema->expects($this->at(1))->method('validate')->with($this->equalTo('2'))->will($this->returnValue(TRUE));
		$itemSchema->expects($this->at(2))->method('validate')->with($this->equalTo('3'))->will($this->returnValue(TRUE));
		$itemSchema->expects($this->at(3))->method('validate')->with($this->equalTo('4'))->will($this->returnValue(TRUE));
		$itemSchema->expects($this->at(4))->method('validate')->with($this->equalTo('5'))->will($this->returnValue(TRUE));

		$this->sut->setType('array');
		$this->sut->setItems($itemSchema);
		$this->assertTrue($this->sut->validate($goodArray));
	}

	/**
	 * It should validate array items using schemata.
	 * @test
	 */
	public function canValidateArrayItemsWithBadArray() {
		$badArray = array('1','2','3',4,'5');

		$itemSchema = $this->getMockBuilder('\itsoneiota\json\JSONSchema')->disableOriginalConstructor()->getMock();
		$itemSchema->expects($this->at(0))->method('validate')->with($this->equalTo('1'))->will($this->returnValue(TRUE));
		$itemSchema->expects($this->at(1))->method('validate')->with($this->equalTo('2'))->will($this->returnValue(TRUE));
		$itemSchema->expects($this->at(2))->method('validate')->with($this->equalTo('3'))->will($this->returnValue(TRUE));
		$itemSchema->expects($this->at(3))->method('validate')->with($this->equalTo( 4 ))->will($this->returnValue(FALSE));
		$itemSchema->expects($this->any())->method('getErrors')->will($this->returnValue(array()));

		$this->sut->setType('array');
		$this->sut->setItems($itemSchema);
		$this->assertFalse($this->sut->validate($badArray));
	}

	/**
	 * It should validate the array items with one schema per position.
	 * @test
	 */
	public function canValidateArrayItemsWithTupleType() {
		$goodArray = array('1',2,TRUE);
		$badArray = array('1',2,'TRUE');

		$a = new JSONSchema();
		$b = new JSONSchema();
		$c = new JSONSchema();

		$a->setType('string');
		$b->setType('integer');
		$c->setType('boolean');

		$this->sut->setType('array');
		$this->sut->setItems(array($a,$b,$c));
		$this->assertTrue($this->sut->validate($goodArray));
		$this->assertFalse($this->sut->validate($badArray));
	}

	/**
	 * It should validate the array items with tuple typing, and additional items allowed.
	 * @test
	 */
	public function canValidateArrayItemsWithTupleTypeAllowingAdditionalItems() {
		$goodArray = array('1',2,TRUE,'a',FALSE,NULL);
		$badArray = array('1',2,'TRUE','a',FALSE,NULL);

		$a = new JSONSchema();
		$b = new JSONSchema();
		$c = new JSONSchema();

		$a->setType('string');
		$b->setType('integer');
		$c->setType('boolean');

		$this->sut->setType('array');
		$this->sut->setItems(array($a,$b,$c));
		$this->assertTrue($this->sut->validate($goodArray));
		$this->assertFalse($this->sut->validate($badArray));
	}

	/**
	 * It should validate the array items with tuple typing and additional items disallowed.
	 * @test
	 */
	public function canValidateArrayItemsWithTupleTypeDisallowingAdditionalItems() {
		$goodArray = array('1',2,TRUE);
		$badArray = array('1',2,TRUE,'a');

		$a = new JSONSchema();
		$b = new JSONSchema();
		$c = new JSONSchema();

		$a->setType('string');
		$b->setType('integer');
		$c->setType('boolean');

		$this->sut->setType('array');
		$this->sut->setItems(array($a,$b,$c));
		$this->sut->setAdditionalItems(FALSE);
		$this->assertTrue($this->sut->validate($goodArray));
		$this->assertFalse($this->sut->validate($badArray));
	}

	/**
	 * It should validate the array items with tuple typing and additional items constrained.
	 * @test
	 */
	public function canValidateArrayItemsWithTupleTypeConstrainingAdditionalItems() {
		$goodArray = array('1',2,TRUE,1,3,9);
		$badArray = array('1',2,TRUE,1,'b',7);

		$a = new JSONSchema();
		$b = new JSONSchema();
		$c = new JSONSchema();

		$a->setType('string');
		$b->setType('integer');
		$c->setType('boolean');

		$additionalItemSchema = new JSONSchema();
		$additionalItemSchema->setType('integer');

		$this->sut->setType('array');
		$this->sut->setItems(array($a,$b,$c));
		$this->sut->setAdditionalItems($additionalItemSchema);
		$this->assertTrue($this->sut->validate($goodArray));
		$this->assertFalse($this->sut->validate($badArray));
	}

	/**
	 * It should validate a number with constraints.
	 * @test
	 */
	public function canValidateNumberWithMinimumAndMaximumConstraints() {
		$this->sut->setType('integer');
		$this->sut->setMinimum(4);
		$this->sut->setMaximum(12);
		$this->assertFalse($this->sut->validate(3));
		for($i=4; $i <= 12; $i++) {
			$this->assertTrue($this->sut->validate($i));
		}
		$this->assertFalse($this->sut->validate(13));
	}

	/**
	 * It should validate a number with constraints.
	 * @test
	 */
	public function canValidateNumberWithExclusiveMinimumAndExclusiveMaximumConstraints() {
		$this->sut->setType('integer');
		$this->sut->setExclusiveMinimum(TRUE);
		$this->sut->setExclusiveMaximum(TRUE);
		$this->sut->setMinimum(4);
		$this->sut->setMaximum(12);
		$this->assertFalse($this->sut->validate(4));
		for($i=5; $i < 12; $i++) {
			$this->assertTrue($this->sut->validate($i));
		}
		$this->assertFalse($this->sut->validate(12));
	}

	/**
	 * It should validate string length using the minLength and maxLength constraints.
	 * @test
	 */
	public function canValidateStringLength() {
		$this->sut->setType('string');
		$this->sut->setMinLength(3);
		$this->sut->setMaxLength(6);
		$this->assertFalse($this->sut->validate('AB'));
		$this->assertTrue($this->sut->validate('ABC'));
		$this->assertTrue($this->sut->validate('ABCD'));
		$this->assertTrue($this->sut->validate('ABCDE'));
		$this->assertTrue($this->sut->validate('ABCDEF'));
		$this->assertFalse($this->sut->validate('ABCDEFG'));
	}

	/**
	 * It should validate a string by matching it against a RegEx pattern.
	 * @test
	 */
	public function canValidateStringAgainstPattern() {
		$this->sut->setType('string');
		$this->sut->setPattern('^BOO+M$');
		$this->assertFalse($this->sut->validate('BOM'));
		$this->assertTrue($this->sut->validate('BOOM'));
		$this->assertTrue($this->sut->validate('BOOOOOOOOOOOM'));
		$this->assertFalse($this->sut->validate('BOOOOOOOOOOOM!'));
	}

	/**
	 * It should validate against an enumeration of possible values.
	 * @test
	 */
	public function canValidateStringAgainstEnum() {
		$this->sut->setType('string');
		$this->sut->setEnum(array('one','two','three',NULL));
		$this->assertTrue($this->sut->validate('one'));
		$this->assertFalse($this->sut->validate('twoo'));
		$this->assertTrue($this->sut->validate('three'));
		$this->assertTrue($this->sut->validate(NULL));
	}

	/**
	 * It should validate a string against the specified format.
	 * @test
	 */
	public function canValidateStringAgainstFormat() {
		$this->sut->setType('string');
		$this->sut->setFormat('date-time');
		$this->assertTrue($this->sut->validate('2012-08-07T14:55:11Z'));
		$this->assertFalse($this->sut->validate('2012-08-07T14:5S:11Z'));

		$this->sut->setFormat('date-time');
		$this->assertTrue($this->sut->validate('2012-08-07'));
		$this->assertFalse($this->sut->validate('2012-O8-07'));

		$this->sut->setFormat('date-time');
		$this->assertFalse($this->sut->validate(''));
		$this->assertFalse($this->sut->validate(NULL));

		$this->sut->setFormat('time');
		$this->assertTrue($this->sut->validate('13:01:00'));
		$this->assertFalse($this->sut->validate('13:61:00'));

		$this->sut->setFormat('color');
		$this->assertTrue($this->sut->validate('blue'));
		$this->assertTrue($this->sut->validate('#00FFAB'));
		$this->assertFalse($this->sut->validate('mauve'));

		$this->sut->setFormat('uri');
		$this->assertTrue($this->sut->validate('http://www.itsitsoneiota.com/about_us.html?person=123'));
		$this->assertTrue($this->sut->validate('www.itsoneiota.co.uk'));
		$this->assertFalse($this->sut->validate('thisisnotaurl'));

		$this->sut->setFormat('phone');
		$this->assertTrue($this->sut->validate('0161 715 8954'));
		$this->assertTrue($this->sut->validate('+01 429 555 1212'));
		$this->assertFalse($this->sut->validate('+01 429 555 12O2')); // There's a letter O in there.

		$this->sut->setFormat('email');
		$this->assertTrue($this->sut->validate('ross.mcfarlane@itsoneiota.co.uk'));
		$this->assertTrue($this->sut->validate('ross@itsitsoneiota.COM'));
		$this->assertFalse($this->sut->validate('ross@itsitsoneiota.madeuptld')); // Made up TLD.
		$this->assertFalse($this->sut->validate('ross. mcfarlane@itsitsoneiota.com')); // Whitespace
	}

	/**
	 * It should validate against an enumeration of possible values.
	 * @test
	 */
	public function canValidateIntegerAgainstEnum() {
		$this->sut->setEnum(array(1,2,3));
		$this->assertTrue($this->sut->validate(1));
		$this->assertFalse($this->sut->validate('2'));
		$this->assertTrue($this->sut->validate(3));
	}

	/**
	 * It should validate against an enumeration of possible values.
	 * @test
	 */
	public function canValidateObjectAgainstEnum() {
		$a = new \stdClass();
		$a->foo = 'bar';

		$b = new \stdClass();
		$b->bat = 'baz';

		$c = new \stdClass();
		$c->one = 1;

		$aMatch = new \stdClass();
		$aMatch->foo = 'bar';

		$bMatch = new \stdClass();
		$bMatch->bat = 'baz';

		$miss = new \stdClass();
		$miss->bat = 'bar';

		$typeMiss = new \stdClass();
		$typeMiss->one = '1';

		$this->sut->setEnum(array($a,$b));
		$this->assertTrue($this->sut->validate($aMatch));
		$this->assertTrue($this->sut->validate($bMatch));
		$this->assertFalse($this->sut->validate($miss));
		$this->assertFalse($this->sut->validate($typeMiss));
	}

	/**
	 * It should validate a schema with a type reference back to itself.
	 * @test
	 */
	public function canValidateRecursiveSchema() {
		$aSchema = new \itsoneiota\json\JSONSchema();
		$aSchema->setType('integer');

		$this->sut->setType('object');
		$this->sut->setProperty('a',$aSchema);
		$this->sut->setProperty('b',$this->sut);
		$this->sut->setAdditionalProperties($this->sut);
		$this->sut->setRequired(['a']);

		$instance = new \stdClass();
		$instance->a = 1;
		$instance->b = new \stdClass();
		$instance->b->a = 1;
		$instance->c = new \stdClass();
		$instance->c->a = 1;

		$this->assertTrue($this->sut->validate($instance));
	}

	/**
	 * It should generate error messages with a path to
	 * the offending value.
	 * @test
	 */
	public function canGenerateErrorMessages() {
		$aSchema = new \itsoneiota\json\JSONSchema();
		$aSchema->setType('object');

		$abSchema = new \itsoneiota\json\JSONSchema();
		$abSchema->setType('integer');

		$aSchema->setProperty('b',$abSchema);

		$this->sut->setType('object');
		$this->sut->setProperty('a',$aSchema);

		$instance = new \stdClass();
		$instance->a = new \stdClass();
		$instance->a->b = 'b'; // Should be an int.

		$this->assertFalse($this->sut->validate($instance));
		$errors = $this->sut->getErrors();
		$this->assertEquals(1, count($errors));
		$error = $errors[0];
		$this->assertEquals('#/a/b', $error->path);
	}

	/**
	 * It should generate error messages with a path to
	 * the offending value.
	 * @test
	 */
	public function canGenerateErrorMessagesWithRecursiveSchema() {
		$aSchema = new \itsoneiota\json\JSONSchema();
		$aSchema->setType('integer');

		$this->sut->setType('object');
		$this->sut->setProperty('a',$aSchema);
		$this->sut->setProperty('b',$this->sut);
		$this->sut->setRequired(['a']);

		$instance = new \stdClass();
		$instance->a = 1;
		$instance->b = new \stdClass();
		$instance->b->a = 'b'; // Should be an int.

		$this->assertFalse($this->sut->validate($instance));
		$errors = $this->sut->getErrors();
		$this->assertEquals(1, count($errors));
		$error = $errors[0];
		$this->assertEquals('#/b/a', $error->path);
	}

	/**
	 * It should validate that a number is divisible by X.
	 * @test
	 */
	public function canValidateNumberWithDivisibleByConstraint() {
		$this->sut->setDivisibleBy(3);
		$this->assertTrue($this->sut->validate(0));
		$this->assertTrue($this->sut->validate(3));
		$this->assertTrue($this->sut->validate(6));
		$this->assertTrue($this->sut->validate(300000000000000000));
		$this->assertFalse($this->sut->validate(2.9));
		$this->assertFalse($this->sut->validate(3.1));
		$this->assertFalse($this->sut->validate(1));
		$this->assertFalse($this->sut->validate(4));
	}

	/**
	 * It should reject an attempt to set 0 as divisibleBy.
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function canRejectZeroInSetDivisibleBy() {
		$this->sut->setDivisibleBy(0);
	}

	/**
	 * It should validate a type where a disallowed type or type union is set.
	 * @test
	 */
	public function canValidateTypeWithDisallow() {
		$aSchema = new \itsoneiota\json\JSONSchema();
		$aSchema->setType('object');
		$this->sut->setType(array('string','integer','object'));

		$this->assertTrue($this->sut->validate(1));
		$this->assertFalse($this->sut->validate(1.5));
		$this->assertTrue($this->sut->validate('I am a string'));
		$this->assertFalse($this->sut->validate(TRUE));
		$this->assertTrue($this->sut->validate(new \stdClass()));

		$this->sut->setDisallow(array('string','boolean',$aSchema));

		$this->assertTrue($this->sut->validate(1));
		$this->assertFalse($this->sut->validate(1.5));
		$this->assertFalse($this->sut->validate('I am a string'));
		$this->assertFalse($this->sut->validate(TRUE));
		$this->assertFalse($this->sut->validate(new \stdClass()));
	}

	/**
	 * It should validate an object with a simple dependency requirement.
	 * @test
	 */
	public function canValidateWithScalarDependency() {
		$dependencies = new \stdClass();
		$dependencies->foo = 'bar';
		$dependencies->drummers = 'carey';

		$this->sut->setDependencies($dependencies);

		$instance = new \stdClass();
		$instance->baz = 'bat';

		$this->assertTrue($this->sut->validate($instance));

		$instance->foo = 'a';

		$this->assertFalse($this->sut->validate($instance));

		$instance->bar = 'b';

		$this->assertTrue($this->sut->validate($instance));

		$instance->drummers = TRUE;

		$this->assertFalse($this->sut->validate($instance));

		$instance->carey = 'danny';

		$this->assertTrue($this->sut->validate($instance));
	}

	/**
	 * It should validate an object with a simple dependency requirement.
	 * @test
	 */
	public function canValidateWithSimpleDependency() {
		$dependencies = new \stdClass();
		$dependencies->foo = 'bar';
		$dependencies->drummers = array('carey','freese','grohl');

		$this->sut->setDependencies($dependencies);

		$instance = new \stdClass();
		$instance->baz = 'bat';

		$this->assertTrue($this->sut->validate($instance));

		$instance->foo = 'a';

		$this->assertFalse($this->sut->validate($instance));

		$instance->bar = 'b';

		$this->assertTrue($this->sut->validate($instance));

		$instance->drummers = TRUE;

		$this->assertFalse($this->sut->validate($instance));

		$instance->carey = 'danny';
		$this->assertFalse($this->sut->validate($instance));
		$instance->freese = 'josh';
		$this->assertFalse($this->sut->validate($instance));
		$instance->grohl = 'dave';

		$this->assertTrue($this->sut->validate($instance));
	}

	/**
	 * It should validate an object with a schema dependency.
	 * @test
	 */
	public function canValidateWithSchemaDependency() {
		$depSchema = new \itsoneiota\json\JSONSchema();
		$depSchema->setType('object');
		$depASchema = new \itsoneiota\json\JSONSchema();
		$depASchema->setType('integer');
		$depSchema->setProperty('A',$depASchema);
		$depSchema->setRequired(['A']);

		$dependencies = new \stdClass();
		$dependencies->foo = $depSchema;

		$this->sut->setDependencies($dependencies);

		$instance = new \stdClass();
		$instance->baz = 'bat';

		$this->assertTrue($this->sut->validate($instance));

		$instance->foo = 'a';

		$this->assertFalse($this->sut->validate($instance));

		$instance->A = 3;

		$this->assertTrue($this->sut->validate($instance));
	}

	/**
	 * It should validate an object with pattern properties.
	 * @test
	 */
	public function canValidateWithPatternProperties() {
		$propSchema = new \itsoneiota\json\JSONSchema();
		$propSchema->setType('object');
		$propASchema = new \itsoneiota\json\JSONSchema();
		$propASchema->setType('integer');
		$propSchema->setProperty('A',$propASchema);
		$propSchema->setRequired(['A']);

		$patternProperties = new \stdClass();
		$pattern = '^BOO+M$';
		$patternProperties->$pattern = $propSchema;

		$this->sut->setType('object');
		$this->sut->setPatternProperties($patternProperties);

		$instance = new \stdClass();
		$instance->foo = 'bar';

		$this->assertTrue($this->sut->validate($instance));

		$instance->BOOOOOOM = 'A';

		$this->assertFalse($this->sut->validate($instance));

		$instance->BOOOOOOM = new \stdClass();
		$instance->BOOOOOOM->A = 7;

		$this->assertTrue($this->sut->validate($instance));

		$instance->BOOM = 'A';

		$this->assertFalse($this->sut->validate($instance));

		$instance->BOOM = new \stdClass();
		$instance->BOOM->A = 7;

		$this->assertTrue($this->sut->validate($instance));
	}

	/**
	 * It should allow a null value through where a string format is set.
	 * @test
	 */
	public function canAllowNullWhereFormatIsSet() {
		$this->sut->setType(array('string','null'));
		$this->sut->setFormat('uri');
		$this->assertTrue($this->sut->validate(NULL), json_encode($this->sut->getErrors()));
	}

	/**
	 * It should allow a property matched by pattern where additional properties are not allowed.
	 * @test
	 */
	public function canAllowPatternPropertyWhereAdditionalPropertiesAreNotAllowed() {
		$propSchema = new \itsoneiota\json\JSONSchema();
		$propSchema->setType('string');

		$this->sut->setProperty('foo', $propSchema);

		$patternProperties = new \stdClass();
		$pattern = "^b\\wr$";
		$patternProperties->$pattern = $propSchema;

		$this->sut->setPatternProperties($patternProperties);

		$this->sut->setAdditionalProperties(FALSE);

		$obj = json_decode('{"foo":"abc","bar":"def"}');
		$this->assertTrue($this->sut->validate($obj),json_encode($this->sut->getErrors()));

		$obj = json_decode('{"foo":"abc","GET":"def"}');
		$this->assertFalse($this->sut->validate($obj),json_encode($this->sut->getErrors()));
	}

	public function assertExampleMatchesSchema() {
		$valid = $this->sut->validate($this->sut->exemplify(JSONSchema::EXAMPLE_MINIMAL));
		$this->assertTrue($valid, "Minimal example failed validation against itself. " . json_encode($this->sut->getErrors()));

		$valid = $this->sut->validate($this->sut->exemplify(\itsoneiota\json\JSONSchema::EXAMPLE_MAXIMAL));
		$this->assertTrue($valid, "Maximal example failed validation against itself. " . json_encode($this->sut->getErrors()));
	}

	/**
	 * It should return an example that will match an empty schema.
	 * @test
	 */
	public function canGetExampleForEmptySchema() {
		$this->assertTrue($this->sut->validate($this->sut->exemplify()));
	}

	/**
	 * It should return the explicitly set example, if there is one.
	 * @test
	 */
	public function canGetExplicitExample() {
		$this->assertNull($this->sut->exemplify());
		$this->sut->setExample('ABC123');
		$this->assertEquals('ABC123', $this->sut->exemplify());
	}

	/**
	 * It should return the explicitly set example, if there is one.
	 * @test
	 */
	public function canGetDefaultIfExplicitExampleIsNotSet() {
		$this->assertNull($this->sut->exemplify());
		$this->sut->setDefault('thisisthedefault');
		$this->assertEquals('thisisthedefault', $this->sut->exemplify());
		$this->sut->setExample('ABC123');
		$this->assertEquals('ABC123', $this->sut->exemplify());
	}

	/**
	 * It should return an enum value at random if no example or default are present.
	 * @test
	 */
	public function canGetRandomEnumValueAsExample() {
		$enum = array('a','b',1,97.5);
		$this->assertNull($this->sut->exemplify());
		$this->sut->setEnum($enum);
		$this->assertContains($this->sut->exemplify(), $enum);
		$this->sut->setDefault('thisisthedefault');
		$this->assertEquals('thisisthedefault', $this->sut->exemplify());
		$this->sut->setExample('ABC123');
		$this->assertEquals('ABC123', $this->sut->exemplify());

	}

	public function simpleTypeProvider(){
		return array(
			'string'=>array('string'),
			'number'=>array('number'),
			'integer'=>array('integer'),
			'boolean'=>array('boolean'),
			'object'=>array('object'),
			'array'=>array('array'),
			'null'=>array('null'),
			'any'=>array('any')
		);
	}

	/**
	 * It should return an example for simple types.
	 * @test
	 * @dataProvider simpleTypeProvider
	 */
	public function canGetExampleForSimpleTypes($type) {
		$this->sut->setType($type);
		$this->assertExampleMatchesSchema();
	}

	/**
	 * It should generate an example for union types.
	 * @test
	 */
	public function canGenerateExampleForUnionTypes() {
		$this->sut->setType(array('object','null'));
		$this->assertSame(NULL, $this->sut->exemplify(JSONSchema::EXAMPLE_MINIMAL));
		$this->assertTrue(is_object($this->sut->exemplify(JSONSchema::EXAMPLE_MAXIMAL)));
	}

	/**
	 * It should generate an example for union types.
	 * @test
	 */
	public function canGenerateExampleForUnionTypesWithReference() {
		$refType = new JSONSchema();
		$refType->setType('string');
		$refType->setFormat('date');
		$this->sut->setType(array($refType));
		$this->assertExampleMatchesSchema();
	}

	/**
	 * It should generate an example where disallowed types have been set.
	 * @test
	 */
	public function canGenerateExampleForTypesWithDisallow() {
		$this->sut->setDisallow('null');
		$this->assertExampleMatchesSchema();

		$this->sut->setDisallow(array('boolean','null','object'));
		$this->assertExampleMatchesSchema();
	}

	/**
	 * It should return strings that match the schema's constraints.
	 * @test
	 */
	public function canGetExampleForStringWithConstraints() {
		$this->sut->setType('string');
		$this->sut->setMinLength(4);
		$this->sut->setMaxLength(6);
		$this->assertExampleMatchesSchema();

		// Any fixed string can't pass both of these tests.
		$this->sut->setMinLength(8);
		$this->sut->setMaxLength(12);
		$this->assertExampleMatchesSchema();
	}

	public function formatProvider(){
		return array(
			'date-time'=>array('date-time'),
			'date'=>array('date'),
			'time'=>array('time'),
			'utc-millisec'=>array('utc-millisec'),
			'regex'=>array('regex'),
			'color'=>array('color'),
			'style'=>array('style'),
			'phone'=>array('phone'),
			'uri'=>array('uri'),
			'email'=>array('email'),
			'ip-address'=>array('ip-address'),
			'ipv6'=>array('ipv6'),
			'host-name'=>array('host-name')
		);
	}

	/**
	 * It should return an example matching the schema's format.
	 * @test
	 * @dataProvider formatProvider
	 */
	public function canGetStringExampleWithFormat($format) {
		$this->sut->setType('string');
		$this->sut->setFormat($format);
		$this->assertExampleMatchesSchema();
	}

	/**
	 * It should return an integer example that matches the schema's constraints.
	 * @test
	 */
	public function canGetIntegerExampleWithConstraints() {
		$this->sut->setType('integer');
		$this->sut->setMinimum(4);
		$this->sut->setMaximum(12);
		$this->assertExampleMatchesSchema();

		$this->sut->setMinimum(15);
		$this->sut->setMaximum(109);
		$this->assertExampleMatchesSchema();

		$this->sut->setExclusiveMinimum(TRUE);
		$this->sut->setExclusiveMaximum(TRUE);
		$this->sut->setMinimum(4);
		$this->sut->setMaximum(6);
		// 5 is the only acceptable example here.
		$this->assertEquals(5, $this->sut->exemplify());
	}

	/**
	 * It should return an integer example that matches the schema's constraints.
	 * @test
	 */
	public function canGetNumberExampleWithConstraints() {
		$this->sut->setType('number');
		$this->sut->setMinimum(4.2);
		$this->sut->setMaximum(12.9);
		$this->assertExampleMatchesSchema();

		$this->sut->setMinimum(15.1);
		$this->sut->setMaximum(109);
		$this->assertExampleMatchesSchema();

		$this->sut->setExclusiveMinimum(TRUE);
		$this->sut->setExclusiveMaximum(TRUE);
		$this->sut->setMinimum(4);
		$this->sut->setMaximum(6);
		$this->assertExampleMatchesSchema();
	}

	/**
	 * It should return an example object matching the schema.
	 * @test
	 */
	public function canGetObjectExample() {
		$nameSchema = new JSONSchema();
		$ageSchema = new JSONSchema();
		$heightSchema = new JSONSchema();

		$nameSchema->setType('string');
		$ageSchema->setType('integer');
		$heightSchema->setType('integer');

		// None of these properties are required.
		$this->sut->setType('object');
		$this->sut->setProperty('name', $nameSchema);
		$this->sut->setProperty('age', $ageSchema);
		$this->sut->setProperty('height', $heightSchema);
		$this->sut->setAdditionalProperties(FALSE);

		$this->assertExampleMatchesSchema();
		// Nothing is set by default.
		$this->assertEquals(new \stdClass(), $this->sut->exemplify());
		$this->assertEquals(new \stdClass(), $this->sut->exemplify(JSONSchema::EXAMPLE_MINIMAL));

		// All properties are set if we ask for a maximal object.
		$this->assertExampleMatchesSchema();
		$maximal = $this->sut->exemplify(JSONSchema::EXAMPLE_MAXIMAL);
		$this->assertTrue(property_exists($maximal, 'name'));
		$this->assertTrue(property_exists($maximal, 'age'));
		$this->assertTrue(property_exists($maximal, 'height'));

		$this->sut->setRequired(['name','age','height']);

		$this->assertExampleMatchesSchema();
	}

	/**
	 * It should return an example object matching the schema.
	 * @test
	 */
	public function canGetObjectExampleWithAdditionalProperties() {
		$nameSchema = new JSONSchema();
		$nameSchema->setType('string');

		$additionalSchema = new JSONSchema();
		$additionalSchema->setType('integer');

		$this->sut->setType('object');
		$this->sut->setProperty('name', $nameSchema);
		$this->sut->setAdditionalProperties(TRUE);
		$this->sut->setRequired(['name']);

		$this->assertExampleMatchesSchema();
		$minimalProperties = array_keys(get_object_vars($this->sut->exemplify()));
		$this->assertEquals(1, count($minimalProperties));

		$maximalProperties = array_keys(get_object_vars($this->sut->exemplify(JSONSchema::EXAMPLE_MAXIMAL)));
		$this->assertTrue(count($maximalProperties)>1);

		// Now try to get additional properties matching a schema.
		$this->sut->setAdditionalProperties($additionalSchema);
		$this->assertExampleMatchesSchema();

		$minimalProperties = array_keys(get_object_vars($this->sut->exemplify()));
		$this->assertEquals(1, count($minimalProperties));

		$maximalProperties = array_keys(get_object_vars($this->sut->exemplify(JSONSchema::EXAMPLE_MAXIMAL)));
		$this->assertTrue(count($maximalProperties)>1);
	}

	/**
	 * It should return an example object matching the schema, with patternProperties.
	 * @test
	 */
	public function canGetObjectExampleWithPatternProperties() {
		$propSchema = new \itsoneiota\json\JSONSchema();
		$propSchema->setType('object');
		$propASchema = new \itsoneiota\json\JSONSchema();
		$propASchema->setType('integer');
		$propSchema->setProperty('A',$propASchema);
		$propSchema->setRequired(['A']);

		$patternProperties = new \stdClass();
		$pattern = '^BOO+M$';
		$patternProperties->$pattern = $propSchema;

		$this->sut->setType('object');
		$this->sut->setPatternProperties($patternProperties);
		$this->sut->setAdditionalProperties(FALSE);

		$examples = new \stdClass();
		$examples->BOOOOOOM = new \stdClass();
		$examples->BOOOOOOM->A = 12;
		$examples->BOOOOOOOOOOOOOOOM = new \stdClass();
		$examples->BOOOOOOOOOOOOOOOM->A = 12;
		$this->sut->setPatternPropertyExamples($examples);

		$this->assertExampleMatchesSchema();
		$maximalExample = $this->sut->exemplify(JSONSchema::EXAMPLE_MAXIMAL);
		$maximalProperties = array_keys(get_object_vars($maximalExample));
		$this->assertTrue(property_exists($maximalExample, 'BOOOOOOM'));
		$this->assertTrue(property_exists($maximalExample, 'BOOOOOOOOOOOOOOOM'));
		$this->assertTrue(count($maximalProperties)>0);
	}

	/**
	 * It should return an example array, in line with constraints.
	 * @test
	 */
	public function canGetArrayExample() {
		$itemSchema = new JSONSchema();
		$itemSchema->setType('string');
		$itemSchema->setMinLength(9);
		$itemSchema->setMaxLength(26);

		$this->sut->setType('array');
		$this->sut->setMinItems(4);
		$this->sut->setMaxItems(8);

		$this->assertExampleMatchesSchema();

		$this->sut->setItems($itemSchema);
		$this->assertExampleMatchesSchema();
	}

	/**
	 * It should return an example array with unique items.
	 * @test
	 */
	public function canGetArrayExampleWithUniqueItems() {
		$itemSchema = new JSONSchema();
		$itemSchema->setType('integer');

		$this->sut->setType('array');
		$this->sut->setMinItems(4);
		$this->sut->setMaxItems(8);
		$this->sut->setItems($itemSchema);
		$this->sut->setUniqueItems(TRUE);

		$this->assertExampleMatchesSchema();
	}

	/**
	 * It should get an example array constrained by a tuple type.
	 * @test
	 */
	public function canGetExampleArrayWithTupleType() {
		$a = new JSONSchema();
		$b = new JSONSchema();
		$c = new JSONSchema();

		$a->setType('string');
		$b->setType('integer');
		$c->setType('boolean');

		$this->sut->setType('array');
		$this->sut->setItems(array($a,$b,$c));

		$this->assertExampleMatchesSchema();

		// Now with additional items.
		$this->sut->setAdditionalItems(TRUE);
		$this->assertExampleMatchesSchema();
		$this->assertEquals(3, count($this->sut->exemplify()));
		$this->assertTrue(count($this->sut->exemplify(JSONSchema::EXAMPLE_MAXIMAL))>3);

		$additionalItemSchema = new JSONSchema();
		$additionalItemSchema->setType('number');
		$additionalItemSchema->setMinimum('101');
		$this->sut->setAdditionalItems($additionalItemSchema);

		$this->assertTrue(count($this->sut->exemplify(JSONSchema::EXAMPLE_MAXIMAL))>3);
	}

	/**
	 * It should get an example integer with a divisible by property.
	 * @test
	 */
	public function canGetExampleForIntegerDivisibleBy() {
		$this->sut->setType('integer');
		$this->sut->setDivisibleBy(9);
		$this->assertExampleMatchesSchema();
	}

	/**
	 * It should permit missing read only properties if the option is set.
	 * @test
	 */
	public function canPermitMissingReadOnlyPropertiesWithOption() {
		$this->sut->setType('object');
		$a = new JSONSchema();
		$a->setReadOnly(TRUE);
		$a->setType('string');
		$a->setMaxLength(11);
		$this->sut->setProperty('a', $a);
		$this->sut->setRequired(['a']);

		$goodObj = new \stdClass();
		$goodObj->a = 'validString';

		$badObj = new \stdClass();
		$badObj->a = 'invalidString';

		$missingObj = new \stdClass();

		$this->assertTrue($this->sut->validate($goodObj));
		$this->assertFalse($this->sut->validate($badObj));
		$this->assertFalse($this->sut->validate($missingObj));

		$this->sut->setOptions(JSONSchema::ALLOW_MISSING_READ_ONLY_PROPERTIES);

		$this->assertTrue($this->sut->validate($goodObj));
		$this->assertFalse($this->sut->validate($badObj)); // Should still validate if present.
		$this->assertTrue($this->sut->validate($missingObj),json_encode($this->sut->getErrors()),JSON_PRETTY_PRINT); // But will allow it to be missing.
	}

	/**
	 * It should permit missing read only properties if the option is set.
	 * @test
	 */
	public function canSetDefaultsWhenValidating() {
		$this->sut->setType('object');
		$a = new JSONSchema();
		$a->setType('integer');
		$a->setDefault(42);
		$this->assertTrue($a->hasDefault());
		$this->sut->setProperty('a', $a);

		$obj = (object)[
			'b' => 'foo',
			'c' => 'bar'
		];

		// No default by default.
		$this->assertTrue($this->sut->validate($obj));
		$this->assertFalse(property_exists($obj, 'a'));

		$this->sut->setOptions(JSONSchema::SET_DEFAULTS_WHEN_VALIDATING);
		$this->assertTrue($this->sut->validate($obj));
		$this->assertTrue(property_exists($obj, 'a'));
		$this->assertEquals(42, $obj->a);
	}
}
