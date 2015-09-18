<?php
namespace itsoneiota\json;
/**
 * Tests using json-schema/JSON-Schema-Test-Suite.
 *
 **/
class JSONSchemaTestSuite extends \PHPUnit_Framework_TestCase {

    public function setUp(){
        $loader = new JSONSchemaAutoLoader('http://itsoneiota.com/schemas', '/dev/null');
        $loader->mapURIToFile('http://json-schema.org/draft-04/schema#', __DIR__.'/../src/metaschema/draft-04.json');
        $this->builder = new JSONSchemaBuilder($loader);
    }

    public function testFileProvider(){
        $dir = new \RecursiveDirectoryIterator(__DIR__.'/../vendor/itsoneiota/json-schema-test-suite/tests/draft4');
        $iterator = new \RecursiveIteratorIterator($dir);

        $tests = [];
        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }
            $JSON = file_get_contents($fileInfo->getRealPath());
            $fileName = $fileInfo->getFileName();
            $decoded = json_decode($JSON);
            foreach ($decoded as $testcase) {
                foreach ($testcase->tests as $test) {
                    $testName = $fileName . ': ' . $test->description;
                    $tests[$testName] = [
                        $testcase->schema,
                        $test->data,
                        $test->valid
                    ];
                }
            }
        }

        return $tests;
    }

  /**
   * It should validate according to the JSON-Schema-Test-Suite
   * @test
   * @dataProvider testFileProvider
   */
  public function canValidate($schema, $data, $expected) {
      $failureMessage = $this->buildFailureMessage($schema, $data, $expected);
      try{
          $schemaObject = $this->builder->inflateSchema($schema);
      }catch(\Exception $e){
        //   echo $failureMessage;
          throw $e;
      }

      $this->assertEquals($expected, $schemaObject->validate($data), $failureMessage . ' ' . json_encode($schemaObject->getErrors(), JSON_PRETTY_PRINT));
  }

  protected function buildFailureMessage($schema, $data, $expected) {
      return json_encode(
        [
            'schema'=>$schema,
            'data'=>$data,
            'expected'=>$expected
        ]
      ,JSON_PRETTY_PRINT);
  }

}
