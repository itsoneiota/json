<?php
namespace itsoneiota\json;
/**
 * Tests for JSONSchemaBuilder.
 * @group JSON
 *
 **/
class JSONSchemaBuilderTest extends \PHPUnit_Framework_TestCase {

	protected $sut;

	public function setUp() {
		$this->sut = new JSONSchemaBuilder();
	}

	/**
	 * It should be able to use anyOf.
	 *
	 * @test
	 */
	public function canResolveIDs() {
		$loader = new DummySchemaLoader();
		$loader->rootschema = '{
		    "id": "http://x.y.z/rootschema#",
		    "type":"object",
		    "properties":{
		    	"int":{"$ref":"dir1/dir2/otherschema#"}
		    }
		}';
		$otherSchemaID = 'http://x.y.z/dir1/dir2/otherschema';
		$loader->$otherSchemaID = '{
		    "id": "http://x.y.z/dir1/dir2/otherschema#",
		    "type":"object",
		    "properties":{
		    	"value":{"$ref":"../middleschema#"}
		    }
		}';

		$middleSchemaID = 'http://x.y.z/dir1/middleschema';
		$loader->$middleSchemaID = '{
		    "id": "http://x.y.z/dir1/middleschema#",
		    "type":"integer"
		}';

		$this->sut = new JSONSchemaBuilder($loader);
		$schema = $this->sut->build('rootschema');

		$instance = json_decode('{"int":{"value":"foo"}}');
		$this->assertFalse($schema->validate($instance));
	}


	/**
	 * It should build a schema from a schema file.
	 * @test
	 */
	public function canBuildFromSchemaFile() {
	}

	/**
	 * It should use the schema loader to replace schemata by looking up their $ref property.
	 * @test
	 */
	public function canReferenceUsingSchemaLoader() {
		$loader = new DummySchemaLoader();
		$loader->root = '{
					"type":"object",
					"properties":{
						"foo":{
							"$ref":"fooType"
						}
					}
				}';
		$loader->fooType = '{
					"type":"integer"
				}';
		$this->sut = new JSONSchemaBuilder($loader);
		$schema = $this->sut->build('root');

		$obj = new \stdClass();
		$obj->foo = 5;

		$this->assertTrue($schema->validate($obj));

		$obj->foo = '5';
		$this->assertFalse($schema->validate($obj));
	}

	/**
	 * It should be able to use allOf as an alternative to extension.
	 *
	 * @test
	 */
	public function canUseAllOfAsAlternativeToExtends() {
		$loader = new DummySchemaLoader();
		$loader->a = '{
			"type":"integer",
			"minimum":3
		}';
		$loader->b = '{
			"type":"number",
			"maximum":4
		}';
		$loader->c = '{
			"allOf":[
				{"$ref":"a"},
				{"$ref":"b"}
			]
		}';

		$this->sut = new JSONSchemaBuilder($loader);
		$schema = $this->sut->build('c');

		$this->assertFalse($schema->validate('object'));
		$this->assertFalse($schema->validate(1.5));
		$this->assertFalse($schema->validate(TRUE));
		$this->assertFalse($schema->validate(new \stdClass()));
		$this->assertFalse($schema->validate(array('a'=>123)));
		$this->assertFalse($schema->validate(NULL));

		$this->assertFalse($schema->validate(2));
		$this->assertTrue($schema->validate(3));
		$this->assertFalse($schema->validate(3.5));
		$this->assertTrue($schema->validate(4));
		$this->assertFalse($schema->validate(5));
	}

	/**
	 * It should be able to use anyOf.
	 *
	 * @test
	 */
	public function canUseAnyOf() {
		$loader = new DummySchemaLoader();
		$loader->lower = '{
			"type":"integer",
			"minimum":2,
			"maximum":3
		}';
		$loader->upper = '{
			"type":"integer",
			"minimum":3,
			"maximum":4
		}';
		$loader->either = '{
			"anyOf":[
				{"$ref":"lower"},
				{"$ref":"upper"}
			]
		}';

		$this->sut = new JSONSchemaBuilder($loader);
		$schema = $this->sut->build('either');

		$this->assertFalse($schema->validate('object'));
		$this->assertFalse($schema->validate(3.5));
		$this->assertFalse($schema->validate(TRUE));
		$this->assertFalse($schema->validate(new \stdClass()));
		$this->assertFalse($schema->validate(array('a'=>123)));
		$this->assertFalse($schema->validate(NULL));

		// Below both.
		$this->assertFalse($schema->validate(1));
		// Matches lower schema.
		$this->assertTrue($schema->validate(2));
		// Matches both.
		$this->assertTrue($schema->validate(3));
		// Matches upper.
		$this->assertTrue($schema->validate(4));
		// Above both.
		$this->assertFalse($schema->validate(5));
	}

	/**
	 * It should be able to use anyOf.
	 *
	 * @test
	 */
	public function canUseOneOf() {
		$loader = new DummySchemaLoader();
		$loader->lower = '{
			"type":"integer",
			"minimum":2,
			"maximum":3
		}';
		$loader->upper = '{
			"type":"integer",
			"minimum":3,
			"maximum":4
		}';
		$loader->one = '{
			"oneOf":[
				{"$ref":"lower"},
				{"$ref":"upper"}
			]
		}';

		$this->sut = new JSONSchemaBuilder($loader);
		$schema = $this->sut->build('one');

		$this->assertFalse($schema->validate('object'));
		$this->assertFalse($schema->validate(3.5));
		$this->assertFalse($schema->validate(TRUE));
		$this->assertFalse($schema->validate(new \stdClass()));
		$this->assertFalse($schema->validate(array('a'=>123)));
		$this->assertFalse($schema->validate(NULL));

		// Below both.
		$this->assertFalse($schema->validate(1));
		// Matches lower schema.
		$this->assertTrue($schema->validate(2));
		// Matches both.
		$this->assertFalse($schema->validate(3));
		// Matches upper.
		$this->assertTrue($schema->validate(4));
		// Above both.
		$this->assertFalse($schema->validate(5));
	}

	/**
	 * The real test.
	 * @test
	 */
	public function canBuildMetaSchema() {
		$metaSchema = $this->sut->build(__DIR__.'/../src/metaschema/draft-03.json');
		$this->assertNotNull($metaSchema);
		$metaSchema4 = $this->sut->build(__DIR__.'/../src/metaschema/draft-04.json');
		$this->assertNotNull($metaSchema4);
	}
}

class DummySchemaLoader implements JSONSchemaLoader {

	public function __set($name,$value) {
		$this->$name = $value;
	}

	public function loadSchema($ref) {
		// Turn commerce.mesh.mx/schemas/base/common/domain/schema/X into X.
		$refParts = parse_url($ref);
		$base = '/schemas/base/common/schema/';
		$baseLen = strlen($base);
		if (isset($refParts['host']) and $refParts['host'] == 'commerce.mesh.mx' and substr_compare($refParts['path'], $base, 0, $baseLen)==0) {
			$ref = substr($refParts['path'], $baseLen);
		}

		if(!property_exists($this, $ref)){
			echo json_encode($refParts);
			throw new \Exception('BAD ' . $ref . "\n");
		}
		return $this->$ref;
	}
}
