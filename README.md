One iota JSON Library
======================

Overview
--------
A [JSON Schema][json-schema] validator for PHP.

Installation
------------
The best way to autoload this package and its dependencies is to include the standard Composer autoloader, `vendor/autoload.php`.

Basic Usage
-----------

The validator consists of two main classes:

- `JSONSchema` represents a schema and can validate input.
- `JSONSchemaBuilder` builds a `JSONSchema` instance from a file.

The way you use the validator will depend on where your JSON schemas are. In the simplest case, you can build a `JSONSchema` instance from a single local file like this:

	use \itsoneiota\json\JSONSchemaBuilder;
	$builder = new JSONSchemaBuilder();
	$schema = $builder->build('/path/to/my/schema.json');

	// Now test some input.
	$input = json_decode('{"foo":0, "bar":1}');
	$success = $schema->validate($input);
	if($success){
		echo "Yay!";
	}else{
		echo "Boo.";
	}

Easy.

### References and Autoloading Schemas
Very often, you'll want schemas to be more complex, and [refer][ref] to other schemas. By default, `JSONSchemaBuilder` will just call `file_get_contents()` on any `$ref` references, so you could just put the full file path in there. That's not very webby, though. Ideally, the schema, and any others it references should be available to clients of your API so that they can see what the rules are up front. The JSON schema spec says that references are URIs, but you probably won't want to fetch them over the internet each time. To help with this, the library includes a `JSONSchemaAutoloader` class.

Much like a PHP autoloader, which maps namespaced class names to local files, `JSONSchemaAutoLoader` maps schema URIs to local files. The autoloader is instantiated with two strings, a base URI, and a base directory. The autoloader will map schemas with URIs beneath the base URI to files under the base directory. For example, if we set up our schema autoloader like this:

	use \itsoneiota\json\JSONSchemaAutoLoader;
	$schemaBaseURI = 'https://example.com/schemas/';
	$schemaBaseDir = '/var/www/app/src/json/';
	$loader = new JSONSchemaAutoLoader($schemaBaseDir, $schemaBaseURI);

then a reference to  `https://example.com/schemas/foo/bar/bat` will attempt to load a file from `/var/www/app/src/json/foo/bar/bat.json`.

To use a loader, just pass it as the constructor argument to the `JSONSchemaBuilder`.

	use \itsoneiota\json\JSONSchemaAutoLoader;
	use \itsoneiota\json\JSONSchemaBuilder;

	$schemaBaseURI = 'https://example.com/schemas/';
	$schemaBaseDir = '/var/www/app/src/json/';
	$loader = new JSONSchemaAutoLoader($schemaBaseDir, $schemaBaseURI);

	// Create the builder with the loader, so it knows where to find files.
	$builder = new JSONSchemaBuilder($loader);

	// This will load the schema at /var/www/app/src/json/foo/bar/bat.json
	$schema = $builder->build('https://example.com/schemas/foo/bar/bat');

	// or you could give a URI relative to the base, like this:
	$sameSchemaDifferentURI = $builder->build('foo/bar/bat');

	// Now test some input.
	$success = $schema->validate($input);

#### Absolute URIs
By default, `JSONSchemaAutoloader` will assume that you don't want to load schemas via HTTP. Doing so will cause a `\RuntimeException` to be thrown. To allow `JSONSchemaAutoloader` to fetch schemas from absolute URIs, pass a `Fetcher` to its constructor.

	$fetcher = new Fetcher();
	$loader = new JSONSchemaAutoLoader($fileBase, $URIBase, $fetcher);

`Fetcher` is an _extremely_ simple wrapper for `file_get_contents()`, but you could always implement your own, more sophisticated thing here if you like.

#### Local Copies of Remote URIs
From time to time, you may want to refer to a schema at a remote URI, but you don't want to have the overhead of fetching it every time. To help with this, the `mapURIToFile()` method does just what its name suggests. Give it an absolute URI and a file path, and it will resolve any requests for the absolute URI directly to the local file.

	$loader->mapURIToFile('http://example.com/random.json', '/var/www/cached-schemas/random.json');

### TODO: Making Sense of Errors

### TODO: Read-Only Properties

Testing
-------
The library's suite of unit tests can be run by calling `vendor/bin/phpunit` from the root of the repository.

Plurals
-------
The correct plural of schema is _schemata_, but since even the spec gets it wrong, we'll have to admit defeat. I know, I know.

[json-schema]: http://www.json-schema.org
[ref]: http://tools.ietf.org/html/draft-pbryan-zyp-json-ref-03
