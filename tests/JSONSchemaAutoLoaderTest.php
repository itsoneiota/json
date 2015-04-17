<?php
namespace itsoneiota\json;
use \org\bovigo\vfs\vfsStream;
use \org\bovigo\vfs\vfsStreamWrapper;
/**
 * Tests for JSONSchemaAutoLoader.
 *
 **/
class JSONSchemaAutoLoaderTest extends \PHPUnit_Framework_TestCase {

  protected $sut;

  public function setUp() {
    vfsStream::setup('SCHEMA_ROOT');
    $this->sut = new JSONSchemaAutoLoader('https://commerce.mesh.mx/stuff/schemas/', vfsStream::url('SCHEMA_ROOT'));
  }

  /**
   * It should
   * @test
   */
  public function canConvertURIsToPaths() {
    /**
      * Set up local directory structure with schema files.
      * SCHEMA_ROOT/a.json
      * SCHEMA_ROOT/foo/b.json
      * SCHEMA_ROOT/foo/bar/bat.json
     */
    $foo = vfsStream::newDirectory('foo')->at(vfsStreamWrapper::getRoot());
    $bar = vfsStream::newDirectory('bar')->at($foo);

    vfsStream::newFile('a.json')->withContent('A')->at(vfsStreamWrapper::getRoot());
    vfsStream::newFile('b.json')->withContent('B')->at($foo);
    vfsStream::newFile('bat.json')->withContent('BAT')->at($bar);

    $this->assertFileExists(vfsStream::url("SCHEMA_ROOT/a.json"));
    $this->assertFileExists(vfsStream::url("SCHEMA_ROOT/foo/b.json"));
    $this->assertFileExists(vfsStream::url("SCHEMA_ROOT/foo/bar/bat.json"));

    $this->assertEquals('A', $this->sut->loadSchema('https://commerce.mesh.mx/stuff/schemas/a'));
    $this->assertEquals('A', $this->sut->loadSchema('https://commerce.mesh.mx/stuff/schemas/a.json')); // With/without extension should be equivalent.
    $this->assertEquals('B', $this->sut->loadSchema('https://commerce.mesh.mx/stuff/schemas/foo/b'));
    $this->assertEquals('BAT', $this->sut->loadSchema('https://commerce.mesh.mx/stuff/schemas/foo/bar/bat'));
    $this->assertNull($this->sut->loadSchema('https://commerce.mesh.mx/stuff/schemas/nonexistent'));
  }

  /**
    * It should load a web URI if an HTTPRequest object is given.
    *
    * @test
   */
  public function canFetchSchemaFromWeb(){
      $fetcher = $this->getMockBuilder('\itsoneiota\json\Fetcher')->disableOriginalConstructor()->getMock();
      $fetcher->expects($this->once())->method('fetch')->with('http://example.com/schemas/remote.json')->will($this->returnValue('Z'));
      $this->sut = new JSONSchemaAutoLoader('https://commerce.mesh.mx/stuff/schemas/', vfsStream::url('SCHEMA_ROOT'), $fetcher);

      $result = $this->sut->loadSchema('http://example.com/schemas/remote.json');
      $this->assertEquals('Z', $result);
  }

  /**
   * It should return not found it a remote URL is given, but no web connection is present.
   *
   * @test
   * @expectedException \RuntimeException
   */
  public function canReturnNotFoundForRemoteURIWhereNoWebConnectionIsGiven(){
    $this->assertNull($this->sut->loadSchema('https://www.google.com'));
  }

  /**
   * It should map a schema URI to an explicit local file path.
   *
   * @test
   */
  public function canMapURIToFile(){
      /**
        * Set up local directory structure with one schema file.
        * SCHEMA_ROOT/extraschemas/myExtraSchema.json
       */
      $extraschemas = vfsStream::newDirectory('extraschemas')->at(vfsStreamWrapper::getRoot());
      vfsStream::newFile('myExtraSchema.json')->withContent('X')->at($extraschemas);

      $localSchemaPath = vfsStream::url("SCHEMA_ROOT/extraschemas/myExtraSchema.json");
      $this->assertFileExists($localSchemaPath);

      // Map a random URI to the local file.
      $this->sut->mapURIToFile('http://example.com/some/random/schema.json', $localSchemaPath);

      $this->assertEquals('X', $this->sut->loadSchema('http://example.com/some/random/schema.json'));
  }
}
