<?php
namespace itsoneiota\json;
/**
 * Builds a JSONSchema from JSON schema definition.
 *
 **/
class JSONSchemaBuilder implements JSONSchemaLoader {
	protected static $types = array('string','number','integer','boolean','object','array','null','any');

	/**
	 * Function used to load schema by name/URI.
	 *
	 * @var callable
	 */
	protected $schemaLoader;

	protected $currentSchema = array();
	protected $resolutionScope = array();

	protected $inflatedSchemaArray = array();

	public function __construct(JSONSchemaLoader $schemaLoader = NULL) {
		$this->schemaLoader = is_null($schemaLoader) ? $this : $schemaLoader;
	}

	/**
	 * Load the schema from file.
	 *
	 * @param string $ref Path to the schema file.
	 * @return string Schema contents as JSON, or NULL if $ref cannot be resolved.
	 */
	public function loadSchema($path) {
		if(!is_readable($path)){
			return NULL;
		}
		return file_get_contents($path);
	}

	protected $options = JSONSchema::STRICT_MODE;

	/**
	 * Set validation options for schemas built by this validator.
	 * @param int $options Bitmask of validation options.
	 */
	public function setOptions($options = JSONSchema::STRICT_MODE) {
		$this->options = NULL === $options ? JSONSchema::STRICT_MODE : $options;
	}

	protected function setCurrentSchema(JSONSchema $schema) {
		$id = $schema->getID();
		$newScope = NULL === $id ? end($this->resolutionScope) : $id;
		array_push($this->resolutionScope, $newScope);
		array_push($this->currentSchema, $schema);
	}

	protected function getCurrentSchema() {
		return end($this->currentSchema);
	}

	protected function getResolutionScope() {
		return end($this->resolutionScope);
	}

	protected function unsetCurrentSchema() {
		array_pop($this->resolutionScope);
		array_pop($this->currentSchema);
	}

	public function resolveURI($path) {
		$resolutionScope = $this->getResolutionScope();
		if (empty($resolutionScope)) {
			return $path;
		}

		if ($path == '#') {
			return $resolutionScope;
		}

		$pathParts = parse_url($path);
    	$joinedPath = http_build_url($resolutionScope, $pathParts, HTTP_URL_JOIN_PATH);

    	// Terrible hack to get rid of double slash on joins.
    	$joinedParts = parse_url($joinedPath);
    	if (isset($joinedParts['path'])) {
    		$joinedParts['path'] = str_replace('//', '/', $joinedParts['path']);
    		$resolvedURI = http_build_url($joinedPath, $joinedParts, HTTP_URL_REPLACE);
    	}else{
    		$resolvedURI = $joinedPath;
    	}

    	return $resolvedURI;
	}

	protected function registerSchema($schema, $key) {
		$this->inflatedSchemaArray[$key] = $schema;
	}

	/**
	 * Build a schema from a path.
	 *
	 * @param string $path Path to schema definition.
	 **/
	public function build($path,$required=FALSE,$readonly=FALSE) {
		$URI = $this->resolveURI($path);
		$key = $this->buildSchemaKey($URI, $required, $readonly);

		// If the schema hasn't been added to the array already do it now.
		if(!array_key_exists($key,$this->inflatedSchemaArray)){
			$schema = NULL;
			$def = $this->fetchSchemaDefinition($URI);
			if (NULL !== $def) {
				if (property_exists($def, 'id')) {
					$key = $this->buildSchemaKey($this->resolveURI($def->id), $required, $readonly);
				}
				$schema = $this->inflateSchema($def,$this->inflatedSchemaArray[$key]);
			}

			if (NULL === $schema) {
				throw new \InvalidArgumentException("Could not find schema at $path (raw path: $path, resolved URI: $URI, resolution scope: {$this->getResolutionScope()}).");
			}

			$this->registerSchema($schema, $key);
		}

		return $this->inflatedSchemaArray[$key];
	}

	/**
	 * Generate a key to use in the array of Loaded schemas.
	 * Key includes Required and Read only properties, Options and Path to the schema.
	 *
	 * @param string $URI
	 * @param boolean $required
	 * @param boolean $readonly
	 * @return string
	 */
	protected function buildSchemaKey($URI, $required=FALSE, $readonly=FALSE) {
		$requiredPrefix = $required ? 'required-true|' : 'required-false|';
		$readonlyPrefix = $readonly ? 'readonly-true|' : 'readonly-false|';
		$keyPrefixes = $requiredPrefix.$readonlyPrefix.$this->options.'|';
		return $keyPrefixes.$URI;
	}

	protected function fetchSchemaDefinition($path) {
		// Allow routes to request a simple type instead of a full schema, e.g. "boolean".
		$def = NULL;
		if (in_array($path, self::$types)) {
			$def = new \stdClass();
			$def->type = $path;
			return $def;
		}

		$JSON = $this->schemaLoader->loadSchema($path);
		$schemaDefinition = json_decode($JSON);
		return $schemaDefinition;
	}

	/**
	 * Inflate a schema.
	 *
	 * @param object $def Decoded JSON schema definition.
	 * @return void
	 */
	public function inflateSchema($def,&$inflatedSchemaLocation=NULL) {
		if (is_a($def,'\itsoneiota\json\JSONSchema')) {
			throw new \InvalidArgumentException('Cannot inflate an instance of \itsoneiota\json\JSONSchema.');
		}
		if($def === FALSE){
			return FALSE;
		}
		if(!is_object($def)){
			throw new \InvalidArgumentException('Schema definition must be an object.');
		}
		$schema = new JSONSchema($def);

		// Assign to the inflated schema location so that subschemas can refer to it.
		$inflatedSchemaLocation=$schema;

		if (NULL !== $this->options) {
			$schema->setOptions($this->options);
		}

		if(property_exists($def, '$ref')) {
			/**
			 * This attribute defines a URI of a schema that contains the full representation of this schema.
			 * When a validator encounters this attribute, it SHOULD replace the current schema with the schema referenced
			 * by the value's URI (if known and available) and re-validate the instance. This URI MAY be relative or absolute,
			 * and relative URIs SHOULD be resolved against the URI of the current schema.
			 */
			$refProperty = '$ref';
				/**
				 * We need the required and readonly properties to send to the build method, so we can check if we've loaded this schema already
				 * with the same required / readonly status (options are also checked in the build method)
				 *
				 */
				$require = property_exists($def, 'required') ? $def->required : FALSE;
				$readonlyvar = property_exists($def, 'readonly') ? $def->readonly : FALSE;

				$refSchema = $this->build($def->$refProperty,$require,$readonlyvar);
				// Override the required property to give the parent schema control.

				if(property_exists($def, 'required')){
					$refSchema->setRequired($def->required);
				}

				if(property_exists($def, 'readonly')){
					$refSchema->setReadOnly($def->readonly);
				}

				if(property_exists($def, 'example')){
					$refSchema->setExample($def->example);
				}

				return $refSchema;

		}
		if(property_exists($def, 'extends')) {
			/**
			 * If this schema extends another, then start with the base schema
			 * and add the present properties from this one.
			 */
			$refProperty = '$ref';
			if(property_exists($def->extends, $refProperty)){
				$extendedSchema = $def->extends->$refProperty;
			}else{
				$extendedSchema = $def->extends;
			}
			$schema = $this->build($extendedSchema);
			$this->unsetCurrentSchema();
		}

		if(property_exists($def, 'id')){
			$schema->setID($this->resolveURI($def->id));
		}

		$this->setCurrentSchema($schema);

		if(property_exists($def, 'definitions')) {
			foreach($def->definitions as $name => $definitionDef) {
				$URI = $this->resolveURI("#/definitions/$name");
				$definitionSchema = $this->inflateSchema($definitionDef);
				if (NULL == $definitionSchema->getID()) {
					$definitionSchema->setID($URI);
				}
				$schemaKey = $this->buildSchemaKey($URI);
				$this->registerSchema($definitionSchema, $schemaKey);
			}
		}

		if(property_exists($def, 'type')) {
			if (is_array($def->type)) {
				$type = @array_map(array($this,'mapType'), $def->type);
			}else{
				$type = $this->mapType($def->type);
			}
			$schema->setType($type);
		}

		if(property_exists($def, 'allOf')) {
			$schema->setAllOf($this->inflateSchemaTuple($def->allOf));
		}
		if(property_exists($def, 'anyOf')) {
			$schema->setAnyOf($this->inflateSchemaTuple($def->anyOf));
		}
		if(property_exists($def, 'oneOf')) {
			$schema->setOneOf($this->inflateSchemaTuple($def->oneOf));
		}

		if(property_exists($def, 'properties')) {
			foreach($def->properties as $name => $propertyDef) {
				$schema->setProperty($name,$this->inflateSchema($propertyDef));
			}
		}
		if(property_exists($def, 'patternProperties')) {
			$patternProperties = new \stdClass();
			foreach($def->patternProperties as $patternProperty => $patternSchema) {
				$patternProperties->$patternProperty = $this->inflateSchema($patternSchema);
			}
			$schema->setPatternProperties($patternProperties);
		}
		if(property_exists($def, 'additionalProperties')) {
			if (!is_bool($def->additionalProperties)) {
			}
			$schema->setAdditionalProperties(is_bool($def->additionalProperties) ? $def->additionalProperties : $this->inflateSchema($def->additionalProperties));
		}
		if(property_exists($def, 'items')) {
			$schema->setItems($this->inflateSchemaTuple($def->items));
		}
		if(property_exists($def, 'additionalItems')) {
			$schema->setAdditionalItems(FALSE === $def->additionalItems ? FALSE : $this->inflateSchema($def->additionalItems));
		}
		if(property_exists($def, 'required')) {
			$schema->setRequired($def->required);
		}
		if(property_exists($def, 'readonly')) {
			$schema->setReadOnly($def->readonly);
		}
		if(property_exists($def, 'dependencies')) {
			$schema->setDependencies($def->dependencies);
		}
		if(property_exists($def, 'minimum')) {
			$schema->setMinimum($def->minimum);
		}
		if(property_exists($def, 'maximum')) {
			$schema->setMaximum($def->maximum);
		}
		if(property_exists($def, 'exclusiveMinimum')) {
			$schema->setExclusiveMinimum($def->exclusiveMinimum);
		}
		if(property_exists($def, 'exclusiveMaximum')) {
			$schema->setExclusiveMaximum($def->exclusiveMaximum);
		}
		if(property_exists($def, 'minItems')) {
			$schema->setMinItems($def->minItems);
		}
		if(property_exists($def, 'maxItems')) {
			$schema->setMaxItems($def->maxItems);
		}
		if(property_exists($def, 'uniqueItems')) {
			$schema->setUniqueItems($def->uniqueItems);
		}
		if(property_exists($def, 'pattern')) {
			$schema->setPattern($def->pattern);
		}
		if(property_exists($def, 'minLength')) {
			$schema->setMinLength($def->minLength);
		}
		if(property_exists($def, 'maxLength')) {
			$schema->setMaxLength($def->maxLength);
		}
		if(property_exists($def, 'enum')) {
			$schema->setEnum($def->enum);
		}
		if(property_exists($def, 'title')) {
			$schema->setTitle($def->title);
		}
		if(property_exists($def, 'format')) {
			$schema->setFormat($def->format);
		}
		if(property_exists($def, 'divisibleBy')) {
			$schema->setDivisibleBy($def->divisibleBy);
		}
		if(property_exists($def, 'disallow')) {
			if(is_array($disallow)){
				foreach($disallow as &$disallowedType) {
					if(is_object($disallowedType)){
						$disallowedType = $this->inflateSchema($disallowedType);
					}
				}
			}else{
				if(is_object($disallow)){
					$disallow = $this->inflateSchema($disallow);
				}
			}
			$schema->setDisallow($def->disallow);
		}
		if(property_exists($def, 'expandable')) {
			$schema->setExpandable($def->expandable);
		}

		/***********************************************************
		 * These properties are really just to help documentation.
		 **********************************************************/
		if(property_exists($def, 'description')){
			$schema->setDescription($def->description);
		}
		if(property_exists($def, 'default')){
			$schema->setDefault($def->default);
		}
		if(property_exists($def, 'example')){
			$schema->setExample($def->example);
		}
		if(property_exists($def, 'patternPropertyExamples')){
			$schema->setPatternPropertyExamples($def->patternPropertyExamples);
		}
		if(property_exists($def, '$schema')){
			$schemaProperty = '$schema';
			// $schema->setSchema($this->build($def->$schemaProperty));
		}
		/***********************************************************
		 **********************************************************/

		$this->unsetCurrentSchema();
		return $schema;
	}

	public function mapType($def) {
		if(is_object($def)){
			return $this->inflateSchema($def);
		}
		if(in_array($def,array('string','number','integer','boolean','object','array','null','any'))){
			return $def;
		}
		throw new \InvalidArgumentException('The type "'.$def.'" is not recognised.');
	}

	/**
	 * Inflate a schema that may be a tuple of schemata.
	 *
	 * @param mixed $def
	 * @return mixed
	 */
	protected function inflateSchemaTuple($def) {
		if(is_array($def)){
			// Tuple Typing
			$result = array();
			foreach($def as $subDef) {
				$result[] = $this->inflateSchema($subDef);
			}
		}else{
			$result = $this->inflateSchema($def);
		}
		return $result;
	}
}
