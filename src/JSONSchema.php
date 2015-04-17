<?php
namespace itsoneiota\json;
/**
 * Represents a JSON Schema
 *
 * @link http://tools.ietf.org/id/draft-zyp-json-schema-03.html
 *
 **/
class JSONSchema {

	// Options for bitmask.
	const STRICT_MODE = 1;
	const ALLOW_MISSING_READ_ONLY_PROPERTIES = 2;
	const SET_DEFAULTS_WHEN_VALIDATING = 4;

	protected static $types = array('string','number','integer','boolean','object','array','null','any');
	protected static $defaultFormats = array('date-time','date','time','utc-millisec','regex','color','style','phone','uri','email','ip-address','ipv6','host-name');

	const DATETIME_REGEX = '/^(\d{4})\D?(0[1-9]|1[0-2])\D?([12]\d|0[1-9]|3[01])(\D?([01]\d|2[0-3])\D?([0-5]\d)\D?([0-5]\d)?\D?(\d{3})?([zZ]|([\+-])([01]\d|2[0-3])\D?([0-5]\d)?)?)?$/';
	const DATE_REGEX = '/^(\d{4})\D?(0[1-9]|1[0-2])\D?([12]\d|0[1-9]|3[01])$/';
	const TIME_REGEX = '/^([01]\d|2[0-3])\D?([0-5]\d)\D?([0-5]\d)?\D?(\d{3})?$/';
	const CSS_COLOR_REGEX = '/^(#([0-9A-Fa-f]{3,6})\b)|(aqua)|(black)|(blue)|(fuchsia)|(gray)|(green)|(lime)|(maroon)|(navy)|(olive)|(orange)|(purple)|(red)|(silver)|(teal)|(white)|(yellow)|(rgb\(\s*\b([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\b\s*,\s*\b([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\b\s*,\s*\b([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\b\s*\))|(rgb\(\s*(\d?\d%|100%)+\s*,\s*(\d?\d%|100%)+\s*,\s*(\d?\d%|100%)+\s*\))$/';
	const URI_REGEX = '/^\b((?:[a-z][\w-]+:(?:\/{1,3}|[a-z0-9%])|www\d{0,3}[.])(?:[^\s()<>]+|\([^\s()<>]+\))+(?:\([^\s()<>]+\)|[^`!()\[\]{};:\'".,<>?«»“”‘’\s]))$/';
	const PHONE_REGEX = '/^\+?[0-9\s]{1,45}$/';
	const EMAIL_REGEX = '/^[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\\.)+(?:[A-Z]{2}|com|org|net|edu|gov|mil|biz|info|mobi|name|aero|asia|jobs|museum)$/i';
	const HOSTNAME_REGEX = '/^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$/';
	const IP_ADDRESS_REGEX = '/^(?:\d{1,3}\.){3}\d{1,3}$/';
	const IPV6_REGEX = '/^(((?=(?>.*?(::))(?!.+\3)))\3?|([\dA-F]{1,4}(\3|:(?!$)|$)|\2))(?4){5}((?4){2}|(25[0-5]|(2[0-4]|1\d|[1-9])?\d)(\.(?7)){3})\z$/i';

	const EXAMPLE_MINIMAL = 0;
	const EXAMPLE_MAXIMAL = 1;
	const LOREM_IPSUM = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent mollis auctor vulputate. Morbi laoreet, orci ac mattis vehicula, urna dui feugiat enim, id elementum sapien nisi sit amet magna. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Nunc diam mauris, fermentum sed sollicitudin sed, aliquam non arcu. Donec eu quam orci. Nunc viverra mauris eget arcu adipiscing laoreet. Morbi fermentum venenatis commodo. Praesent euismod leo neque. Nulla consequat vestibulum nunc a scelerisque. Etiam vitae erat nec diam varius euismod sed a leo. Etiam pharetra lorem sit amet magna mattis feugiat. Pellentesque eu massa id ligula sollicitudin mollis ut non dolor.';

	protected $ID = NULL;
	protected $description = NULL;
	protected $example = NULL;
	protected $exampleSet = FALSE; // Has the example been set?
	protected $patternPropertyExamples = NULL;
	protected $default = NULL;
	protected $defaultSet = FALSE; // Has the example been set?
	protected $type = 'any';
	protected $properties = array();
	protected $patternProperties = NULL;
	protected $additionalProperties = NULL;
	protected $items = NULL;
	protected $additionalItems = TRUE;
	protected $required = [];
	protected $readOnly = FALSE;
	protected $dependencies;
	protected $minimum = NULL;
	protected $maximum = NULL;
	protected $exclusiveMinimum = NULL;
	protected $exclusiveMaximum = NULL;
	protected $minItems = NULL;
	protected $maxItems = NULL;
	protected $uniqueItems = FALSE;
	protected $pattern = NULL;
	protected $minLength = NULL;
	protected $maxLength = NULL;
	protected $enum = NULL;
	protected $title;
	protected $format = NULL;
	protected $divisibleBy;
	protected $disallow;
	protected $ref; // Equivalent to $ref in the JSON Schema spec.

	protected $allOf = array();
	protected $anyOf = array();
	protected $oneOf = array();

	protected $errors;
	protected $errorContainer;

	public $def;

	public function __construct($def=NULL) {
		$this->def = $def;
		$this->errors = array();
		$this->errorContainer = $this;
		$this->default = new UnsetValue();
	}

	// Options
	protected $allowMissingReadOnlyProperties = FALSE;
	protected $setDefaultsWhenValidating = FALSE;

	/**
	 * Set validation options.
	 *
	 * @param int $options Bitmask of validation options.
	 */
	public function setOptions($options) {
		$strictMode = (boolean)($options & self::STRICT_MODE);
		$this->allowMissingReadOnlyProperties = (!$strictMode and $options & self::ALLOW_MISSING_READ_ONLY_PROPERTIES);
		$this->setDefaultsWhenValidating = (boolean)($options & self::SET_DEFAULTS_WHEN_VALIDATING);
	}

	/**
	 * Assert that the given type is a valid JSON Schema type.
	 *
	 * @param mixed $type
	 * @return void
	 * @throws \InvalidArgumentException if the type is invalid.
	 */
	protected function assertTypeIsValid($type) {
		if ($this->isAJSONSchema($type)) {
			if ($this === $type) {
				throw new \InvalidArgumentException('Reference type cannot be a link to self.');
			}
		}elseif(!in_array($type, self::$types, TRUE)) {
			$message = is_string($type) ? "Type '$type' is not recognised." : "Type is not recognised.";
			throw new \InvalidArgumentException($message);
		}
	}

	/**
	 * Set the schema's ID.
	 *
	 * @param string $ID
	 */
	public function setID($ID) {
		$this->ID = $ID;
	}
	/**
	 * Get the schema's ID.
	 *
	 * @return string
	 */
	public function getID() {
		return $this->ID;
	}

	/**
	 * Set the schema's description.
	 *
	 * @param string $description
	 */
	public function setDescription($description) {
		$this->description = $description;
	}
	/**
	 * Get the schema's description.
	 *
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

	public function setDefault($default){
		$this->default = $default;
		/**
		 * Since the default might evaluate to FALSE,
		 * we can't rely on if($this->default) to test for
		 * an default.
		 */
		$this->defaultSet = TRUE;
	}

	public function hasDefault(){
		return $this->defaultSet;
	}

	public function getDefault(){
		return $this->default;
	}

	public function setExample($example){
		$this->example = $example;
		/**
		 * Since the example might evaluate to FALSE,
		 * we can't rely on if($this->example) to test for
		 * an example.
		 */
		$this->exampleSet = TRUE;
	}

	public function getExample(){
		return $this->example;
	}

	public function setPatternPropertyExamples(\stdClass $patternPropertyExamples){
		$this->patternPropertyExamples = $patternPropertyExamples;
	}

	public function getpatternPropertyExamples() {
		return $this->patternPropertyExamples;
	}

	public function setSchema(JSONSchema $schema){
		$this->schema = $schema;
	}

	public function getSchema(){
		return $this->schema;
	}

	/**
	 * Set the type.
	 *
	 * @param mixed $type
	 * @return void
	 * @throws \InvalidArgumentException if the type is invalid.
	 */
	public function setType($type){
		if(is_array($type)){
			foreach($type as $thisType) {
				$this->assertTypeIsValid($thisType);
			}
			usort($type, function($a,$b){return is_object($a) - is_object($b);});
		}else{
			$this->assertTypeIsValid($type);
		}
		$this->type = $type;
	}

	/**
	 * Set the allOf schema array.
	 *
	 * @param array Array of JSONSchema instances.
	 * @return void
	 * @throws \InvalidArgumentException if the array is empty, or if it contains anything other than a JSONSchema instances.
	 */
	public function setAllOf(array $allOf) {
		$this->assertSchemaArrayIsValid($allOf);
		$this->allOf = $allOf;
	}

	/**
	 * Set the anyOf schema array.
	 *
	 * @param array Array of JSONSchema instances.
	 * @return void
	 * @throws \InvalidArgumentException if the array is empty, or if it contains anything other than a JSONSchema instances.
	 */
	public function setAnyOf(array $anyOf) {
		$this->assertSchemaArrayIsValid($anyOf);
		$this->anyOf = $anyOf;
	}

	/**
	 * Set the oneOf schema array.
	 *
	 * @param array Array of JSONSchema instances.
	 * @return void
	 * @throws \InvalidArgumentException if the array is empty, or if it contains anything other than a JSONSchema instances.
	 */
	public function setOneOf(array $oneOf) {
		$this->assertSchemaArrayIsValid($oneOf);
		$this->oneOf = $oneOf;
	}

	protected function assertSchemaArrayIsValid(array $schemaArray) {
		if (count($schemaArray) < 1) {
			throw new \InvalidArgumentException('This array MUST have at least one element.');
		}
		foreach ($schemaArray as $schema) {
			if (!$this->isAJSONSchema($schema)) {
				throw new \InvalidArgumentException('Elements of the array MUST be objects. Each object MUST be a valid JSON Schema.');
			}
		}
	}

	/**
	 * Set a property schema.
	 *
	 * @param string $name Name of the property being defined.
	 * @param JSONSchema $schema Schema defining the property.
	 * @return void
	 */
	public function setProperty($name,JSONSchema $schema){
		$this->properties[$name] = $schema;
	}

	/**
	 * Set pattern property schemas.
	 *
	 * @param \stdClass $patternProperties
	 * @return void
	 * @throws \InvalidArgumentException if pattern property values are not instances of JSONSchema.
	 */
	public function setPatternProperties(\stdClass $patternProperties){
		foreach($patternProperties as $pattern => $schema) {
			$pattern = '/' . $pattern . '/';
			if(!$this->validateRegex($pattern)){
				throw new \InvalidArgumentException('Cannot compile property pattern "'.$pattern.'"');
			}
			if (!$this->isAJSONSchema($schema)) {
				throw new \InvalidArgumentException('Pattern property values must be schemas.');
			}
		}
		$this->patternProperties = $patternProperties;
	}
	/**
	 * Set a schema for additional properties.
	 *
	 * @param JSONSchema $schema Schema defining additional properties.
	 * @return void
	 */
	public function setAdditionalProperties($schema){
		if(!(is_bool($schema) || $this->isAJSONSchema($schema))){
			throw new \InvalidArgumentException('Additional properties must be either boolean or a schema.');
		}
		$this->additionalProperties = $schema;
	}
	/**
	 * Set the schema used to validate array items.
	 *
	 * @param mixed $schema JSONSchema, or array of JSONSchema objects.
	 * @return void
	 */
	public function setItems($schema=NULL){
		$this->items = $schema;
	}
	public function setAdditionalItems($schema=NULL) {
		$this->additionalItems = $schema;
	}
	/**
	 * Set the required attribute.
	 *
	 * @param array $required
	 * @return void
	 * @throws \InvalidArgumentException if $required is not boolean.
	 */
	public function setRequired($required){
		if(!is_array($required)){
			throw new \InvalidArgumentException('\'required\' property must be an array (draft 4).');
		}
		$this->required = $required;
	}
	/**
	 * Is the property defined by this schema required?
	 *
	 * @return boolean TRUE if the property is required, FALSE otherwise.
	 */
	public function propertyIsRequired($name) {
		return in_array($name, $this->required);
	}
	/**
	 * Set the readOnly attribute.
	 *
	 * @param boolean $readOnly
	 * @return void
	 * @throws \InvalidArgumentException if $readOnly is not boolean.
	 */
	public function setReadOnly($readOnly){
		if(!is_bool($readOnly)){
			throw new \InvalidArgumentException('\'readOnly\' property must be boolean.');
		}
		$this->readOnly = $readOnly;
	}
	/**
	 * Is the property defined by this schema readOnly?
	 *
	 * @return boolean TRUE if the property is readOnly, FALSE otherwise.
	 */
	public function isReadOnly() {
		return $this->readOnly;
	}
	/**
	 * Set the schema dependencies.
	 *
	 * @param mixed $dependencies \stdClass or JSONSchema describing dependencies.
	 */
	public function setDependencies(\stdClass $dependencies){
		foreach($dependencies as $dependency) {
			if(is_array($dependency)){
				foreach($dependency as $property) {
					if(!is_string($property)){
						throw new \InvalidArgumentException('Items of a dependency list must be strings.');
					}
				}
			}elseif($this->isAJSONSchema($dependency)){
				// Pass
			}else{
				if(!is_string($dependency)){
					throw new \InvalidArgumentException('Dependencies must be strings.');
				}
			}
		}
		$this->dependencies = $dependencies;
	}

	public function getDependencies() {
		return $this->dependencies;
	}

	/**
	 * Set minimum value for numeric instances.
	 *
	 * @param numeric $minimum
	 * @return void
	 * @throws \InvalidArgumentException if $minimum is not numeric.
	 */
	public function setMinimum($minimum){
		if(!is_numeric($minimum)){
			throw new \InvalidArgumentException('minimum must be numeric.');
		}
		$this->minimum = $minimum;
	}
	/**
	 * Set maximum value for numeric instances.
	 *
	 * @param numeric $maximum
	 * @return void
	 * @throws \InvalidArgumentException if $maximum is not numeric.
	 */
	public function setMaximum($maximum){
		if(!is_numeric($maximum)){
			throw new \InvalidArgumentException('Maximum must be numeric.');
		}
		$this->maximum = $maximum;
	}
	/**
	 * Set whether minimum value for numeric instances is exclusive.
	 *
	 * @param boolean $exclusiveMinimum
	 * @return void
	 * @throws \InvalidArgumentException if $exclusiveMinimum is not boolean.
	 */
	public function setExclusiveMinimum($exclusiveMinimum){
		if(!is_bool($exclusiveMinimum)){
			throw new \InvalidArgumentException('ExclusiveMinimum must be boolean.');
		}
		$this->exclusiveMinimum = $exclusiveMinimum;
	}
	/**
	 * Set whether maximum value for numeric instances is exclusive.
	 *
	 * @param boolean $exclusiveMinimum
	 * @return void
	 * @throws \InvalidArgumentException if $exclusiveMaximum is not boolean.
	 */
	public function setExclusiveMaximum($exclusiveMaximum){
		if(!is_bool($exclusiveMaximum)){
			throw new \InvalidArgumentException('ExclusiveMaximum must be boolean.');
		}
		$this->exclusiveMaximum = $exclusiveMaximum;
	}
	/**
	 * Set minimum number of items for an array instance.
	 *
	 * @param numeric $minItems
	 * @return void
	 * @throws \InvalidArgumentException if $minItems is not an integer.
	 */
	public function setMinItems($minItems){
		if(gettype($minItems)!=='integer'){
			throw new \InvalidArgumentException('minItems must be an integer.');
		}
		$this->minItems = $minItems;
	}
	/**
	 * Set maximum number of items for an array instance.
	 *
	 * @param numeric $maxItems
	 * @return void
	 * @throws \InvalidArgumentException if $maxItems is not an integer.
	 */
	public function setMaxItems($maxItems){
		if(gettype($maxItems)!=='integer'){
			throw new \InvalidArgumentException('MaxItems must be an integer.');
		}
		$this->maxItems = $maxItems;
	}
	/**
	 * Set uniqueItems attribute, indicating that all items in an array MUST be unique.
	 *
	 * @param boolean $uniqueItems
	 * @return void
	 * @throws \InvalidArgumentException if $uniqueItems is not boolean.
	 */
	public function setUniqueItems($uniqueItems){
		if(!is_bool($uniqueItems)){
			throw new \InvalidArgumentException('uniqueItems must be boolean.');
		}
		$this->uniqueItems = $uniqueItems;
	}
	/**
	 * Set a regular expression pattern to validate string instances.
	 *
	 * @param string $pattern
	 * @return void
	 * @throws \InvalidArgumentException if $pattern is not a string, or cannot be compiled as a regular expression.
	 */
	public function setPattern($pattern){
		if(!is_string($pattern)){
			throw new \InvalidArgumentException('pattern must be a string.');
		}
		$pattern = '/' . $pattern . '/';
		if(!$this->validateRegex($pattern)){
			throw new \InvalidArgumentException('Could not compile pattern.');
		}
		$this->pattern = $pattern;
	}
	protected function validateRegex($pattern) {
		// Try a test to see if the regex is valid
		// We'll get FALSE and a warning if this doesn't work, so swallow the error.
		$testResponse = @preg_match($pattern,'thisisjustatest');
		return FALSE === $testResponse ? FALSE : TRUE;
	}
	/**
	 * Set the minimum length of a string instance.
	 *
	 * @param int $minLength
	 * @return void
	 * @throws \InvalidArgumentException if $minLength is not an integer.
	 */
	public function setMinLength($minLength){
		if(gettype($minLength)!=='integer'){
			throw new \InvalidArgumentException('minLength must be an integer.');
		}
		$this->minLength = $minLength;
	}
	/**
	 * Set the maximum length of a string instance.
	 *
	 * @param int $maxLength
	 * @return void
	 * @throws \InvalidArgumentException if $maxLength is not an integer.
	 */
	public function setMaxLength($maxLength){
		if(gettype($maxLength)!=='integer'){
			throw new \InvalidArgumentException('maxLength must be an integer.');
		}
		$this->maxLength = $maxLength;
	}
	/**
	 * Set the enumeration of all possible values that are valid for the instance.
	 *
	 * @param array $enum
	 * @return void
	 */
	public function setEnum(array $enum){
		$this->enum = $enum;
	}

	/**
	 * Set the schema's title. For descriptive purposes only.
	 *
	 * @param string $title
	 * @return void
	 * @throws \InvalidArgumentException if $title is not a string.
	 */
	public function setTitle($title){
		if(!is_string($title)){
			throw new \InvalidArgumentException('Title must be a string.');
		}
		$this->title = $title;
	}
	/**
	 * Get the schema's title.
	 *
	 * @return string The schema's title.
	 */
	public function getTitle(){
		return $this->title;
	}
	/**
	 * Set the format to use.
	 *
	 * @param string $format
	 * @return void
	 * @throws \InvalidArgumentException if the format is not recognised.
	 */
	public function setFormat($format){
		if(!in_array($format,self::$defaultFormats)){
			throw new \InvalidArgumentException("$format is not a valid format.");
		}
		$this->format = $format;
	}
	/**
	 * Set the number by which a numeric instance must be divisible by.
	 *
	 * @param numeric $divisibleBy
	 * @return void
	 * @throws \InvalidArgumentException if divisibleBy is not numeric or is 0.
	 */
	public function setDivisibleBy($divisibleBy){
		if(!is_numeric($divisibleBy)){
			throw new \InvalidArgumentException('divisibleBy must be numeric.');
		}
		if($divisibleBy == 0){
			throw new \InvalidArgumentException('divisibleBy should not be 0.');
		}
		$this->divisibleBy = $divisibleBy;
	}
	/**
	 * Set type(s) that are NOT allowed.
	 *
	 * @param $disallow Type or types disallowed by the schema.
	 * @return void
	 * @throws \InvalidArgumentException if the type or type union is not valid.
	 */
	public function setDisallow($disallow){
		if(is_array($disallow)){
			foreach($disallow as $thisType) {
				$this->assertTypeIsValid($thisType);
			}
			usort($disallow, function($a,$b){return is_object($a) - is_object($b);});
		}else{
			$this->assertTypeIsValid($disallow);
		}
		$this->disallow = $disallow;
	}

	/**
	 * Add an error to the list.
	 *
	 * @param string $path
	 * @param string $message
	 * @return void
	 */
	protected function addError($message, $path='') {
		$error = new \stdClass();
		$error->path = $path;
		$error->message = $message;
		$this->errorContainer->errors[] = $error;
	}

	/**
	 * Add errors from a sub-schema.
	 *
	 * @param string $path
	 * @param array $errors
	 * @return void
	 */
	public function addSubErrors($path,$errors) {
		foreach($errors as $error) {
			$subPath = ''==$error->path ? urlencode($path) : urlencode($path)."/$error->path";
			$this->addError($error->message,$subPath);
		}
	}

	/**
	 * Get details of the errors found, if any.
	 *
	 * @return array
	 */
	public function getErrors() {
		$pathMapper = function($lm){
			$lm->path = '#'.(''==$lm->path ? '' : '/').$lm->path; return $lm;
		};
		return array_map($pathMapper, $this->errorContainer->errors);
	}

	public function validate($value, \stdClass $errorContainer=NULL) {
		$stackedErrorContainer = FALSE;
		if(NULL === $errorContainer){
			$this->errorContainer = $this;
		}else{
			$oldErrorContainer = $this->errorContainer;
			$stackedErrorContainer = TRUE;
			$this->errorContainer = $errorContainer;
		}
		$this->errorContainer->errors = array();

		$this->applyDefaults($value);

		$valid =	$this->validateType($this->type,$value) &&
					$this->validateAllOf($value) &&
					$this->validateAnyOf($value) &&
					$this->validateOneOf($value) &&
					$this->validateDisallowedTypes($value) &&
					$this->validateNumber($value) &&
					$this->validateRequiredProperties($value) &&
					$this->validateObjectProperties($value) &&
					$this->validateObjectDependencies($value) &&
					$this->validateArray($value) &&
					$this->validateString($value) &&
					$this->validateAgainstEnum($value) &&
					$this->validateAgainstFormat($value);

		if($stackedErrorContainer){
			$this->errorContainer = $oldErrorContainer;
		}

		return $valid;
	}

    /**
     * Utility function that checks if the given thing is an instance of JSONSchema.
     *
     */
    protected function isAJSONSchema($thing){
      return is_a($thing, '\itsoneiota\json\JSONSchema');
    }

	protected function validateSubSchema(JSONSchema $schema, $value, $path='', $generateErrors=TRUE) {
		$errorContainer = new \stdClass();
		$errorContainer->errors = array();
		$valid = $schema->validate($value,$errorContainer);
		if(!$valid && $generateErrors){
			$this->addSubErrors($path,$errorContainer->errors);
		}
		return $valid;
	}

	/**
	 * Validate the given value against the schema's type.
	 *
	 * @param mixed $type
	 * @param mixed $value
	 * @param boolean $generateError Should a failure generate an error? This allows union type checks to avoid errors for every type.
	 * @param boolean $forDisallow Are we checking types that are disallowed? In that case, be strict about type matching.
	 * @return boolean
	 */
	protected function validateType($type,$value,$generateError=TRUE, $forDisallow=FALSE) {
		if(is_array($type)) {
			// One type in the array MUST pass.
			foreach($type as $thisType) {
				if($this->validateType($thisType,$value,FALSE, $forDisallow)){
					return TRUE;
				}
			}
			if($generateError){
				$actualType = gettype($value);
				$types = implode(',', array_map(array($this,'mapTypeName'), $this->type));
				$message = "$actualType is not in type union [$types]";
				$this->addError($message);
			}
			return FALSE;
		}

		if ($this->isAJSONSchema($type)) {
			$errorContainer = new \stdClass();
			$errorContainer->errors = array();
			if(!$this->validateSubSchema($type, $value, '', $generateError)){
				return FALSE;
			}
		}

		// If the enum permits nulls, then we don't need to validate the type.
		if (NULL===$value and is_array($this->enum) and in_array(NULL, $this->enum)) {
			return TRUE;
		}

		$valid = TRUE;
		switch ($type) {
			case 'any':
				break;
			case 'null':
				if(!is_null($value)){
					$valid = FALSE;
				}
				break;
			case 'string':
				if(!is_string($value)){
					$valid = FALSE;
				}
				break;
			case 'number':
				if(!in_array(gettype($value),array('integer','double'))){
					$valid = FALSE;
				}
				break;
			case 'integer':
			case 'array':
			case 'object':
			case 'boolean':
				if(gettype($value) !== $type){
					$valid = FALSE;
				}
				break;
		}
		if(!$valid && $generateError){
			$actualType = gettype($value);
			$type = is_null($this->type) ? 'null' : "'$this->type'";
			$message = "$actualType found where $type is required.";
			$this->addError($message);
		}
		return $valid;
	}

	protected function mapTypeName($type) {
		if($this->isAJSONSchema($type)){
			$title = $type->getTitle();
			$id = $type->getID();
			return is_null($id) ? (is_null($title) ? '$ref' : "\{$title\}") : $id;
		}
		return $type;
	}

	public function validateAllOf($value) {
		$valid = TRUE;
		$schemasFailed = array();
		foreach ($this->allOf as $schema) {
			// Don't capture errors from subschemas.
			if(!$schema->validateSubSchema($schema, $value, '', FALSE)){
				$valid = FALSE;
				$schemasFailed[] = $schema;
			}
		}

		if(!$valid){
			$typesMatched = implode(',', array_map(array($this,'mapTypeName'), $schemasFailed));
			$message = "Value failed validation against the following schemas [$typesMatched]. It must match them all.";
			$this->addError($message);
		}
		return $valid;
	}

	public function validateAnyOf($value) {
		if (empty($this->anyOf)) {
			return TRUE;
		}
		$valid = FALSE;
		foreach ($this->anyOf as $schema) {
			// Don't capture errors from subschemas.
			if($schema->validateSubSchema($schema, $value, '', FALSE)){
				return TRUE;
			}
		}
		if (!$valid) {
			$types = implode(',', array_map(array($this,'mapTypeName'), $this->anyOf));
			$message = "Value did not match any of the schemas in [$types]. It must match at least one.";
			$this->addError($message);
		}
		return $valid;
	}

	public function validateOneOf($value) {
		if (empty($this->oneOf)) {
			return TRUE;
		}
		$schemasMatched = array();
		foreach ($this->oneOf as $schema) {
			if($schema->validateSubSchema($schema, $value, '', FALSE)){
				$schemasMatched[] = $schema;
			}
		}

		$numSchemasMatched = count($schemasMatched);
		if ($numSchemasMatched == 1) {
			return TRUE;
		}

		if ($numSchemasMatched == 0) {
			$types = implode(',', array_map(array($this,'mapTypeName'), $this->oneOf));
			$message = "Value did not match any of the schemas in [$types]. It must match exactly one.";
		}elseif ($numSchemasMatched > 1) {
			$typesMatched = implode(',', array_map(array($this,'mapTypeName'), $schemasMatched));
			$message = "Value matched the following schemas [$typesMatched]. It must exactly one.";
		}
		$this->addError($message);
		return FALSE;
	}

	/**
	 * Ensure that the given value doesn't match any disallowed types.
	 *
	 * @param mixed $value
	 * @return boolean TRUE if the value does not match any disallowed types, FALSE otherwise.
	 */
	protected function validateDisallowedTypes($value) {
		if (NULL === $this->disallow) {
			return TRUE;
		}
		$valid = !$this->validateType($this->disallow, $value, FALSE, TRUE);
		if(!$valid){
			$this->addError('Value matches disallowed type.');
		}
		return $valid;
	}

	protected function applyDefaults($value){
		if(!$this->setDefaultsWhenValidating){
			return;
		}
		foreach($this->properties as $name => $schema) {
			/**
			* Only apply the default if the default value has actually been set on the schema.
			* We don't want to set a value to NULL if no default was set.
			*/
			if($schema && $schema->hasDefault()){
				$value->$name = $schema->getDefault();
			}
		}
	}

	protected function validateRequiredProperties($value) {
		$valid = TRUE;
		foreach($this->required as $requiredProperty) {
			if(!property_exists($value,$requiredProperty)){
				$propertySchema = array_key_exists($requiredProperty, $this->properties) ? $this->properties[$requiredProperty] : NULL;
				$propertyIsReadOnly = NULL === $propertySchema ? FALSE : $propertySchema->isReadOnly();
				if(!($this->allowMissingReadOnlyProperties && $propertyIsReadOnly)){
					$this->addError("Property '$requiredProperty' is required and is not present." . var_export($this->allowMissingReadOnlyProperties,TRUE). var_export($propertyIsReadOnly,TRUE));
					$valid = FALSE;
				}elseif($this->setDefaultsWhenValidating){
					/**
					* Only apply the default if the default value has actually been set on the schema.
					* We don't want to set a value to NULL if no default was set.
					*/
					if($propertySchema && $propertySchema->hasDefault()){
						$value->$requiredProperty = $propertySchema->getDefault();
					}
				}
			}
		}

		return $valid;
	}

	/**
	 * Validate the given object's properties.
	 *
	 * @param object $value
	 * @return boolean
	 */
	protected function validateObjectProperties($value) {
		if(!is_object($value)){
			return TRUE;
		}
		$valid = TRUE;

		// Now validate each property.
		foreach($value as $property => $propertyValue) {
			// Explicitly defined properties.
			if (isset($this->properties[$property])) {
				$propertyIsValid = $this->validateSubSchema($this->properties[$property], $propertyValue, $property);
				$valid = $valid && $propertyIsValid;
				continue;
			}

			// Pattern properties
			if(!is_null($this->patternProperties)){
				$matchedAgainstPattern = FALSE;
				foreach($this->patternProperties as $pattern => $schema) {
					$pattern = '/' . $pattern . '/';
					if(preg_match($pattern, $property)){
						$matchedAgainstPattern = TRUE;
						if(!$this->validateSubSchema($schema,$propertyValue,$property)){
							$valid = FALSE;
						}
						break;
					}
				}
				if ($matchedAgainstPattern) {
					continue;
				}
			}

			// We now have an additional property.
			// Check additional property constraints.
			if(is_object($this->additionalProperties)){
				if(!$this->validateSubSchema($this->additionalProperties,$propertyValue,$property)){
					$valid = FALSE;
				}
				continue;
			}

			// Additional properties are disallowed.
			if($this->additionalProperties === FALSE){
				$this->addError("The property '$property' is not defined in the object schema and additional properties are disallowed. Allowed properties are " . implode(', ', array_keys($this->properties)));
				$valid = FALSE;
				continue;
			}
		}

		if (property_exists($value, 'expandable')) {
			if (property_exists($value, 'required') && is_scalar($value->required) && $value->required ) {
				$this->addError('The object is marked as expandable and required. If an object is expandable it cannot be required.');
				$valid = FALSE;
			}
		}
		return $valid;
	}
	/**
	 * Validate array items agains the items and additionalItems constraints.
	 *
	 * @param array $value
	 * @return boolean
	 */
	protected function validateArray($value) {
		if(!is_array($value)){
			return TRUE;
		}
		$numItems = count($value);
		// Test array length.
		if(!is_null($this->minItems) && $numItems < $this->minItems) {
			$this->addError("Array contains $numItems items, minimum is $this->minItems.");
			return FALSE;
		}
		if(!is_null($this->maxItems) && $numItems > $this->maxItems) {
			$this->addError("Array contains $numItems items, maximum is $this->maxItems.");
			return FALSE;
		}
		// Test for unique items.
		if($this->uniqueItems) {
			$currentItems = array();
			foreach($value as $element) {
				// Rule out equal but non-identical objects too.
				$beStrict = !is_object($element);
				if(in_array($element,$currentItems,$beStrict)){
					$this->addError('Array items are not unique.');
					return FALSE;
				}
				$currentItems[] = $element;
			}
		}
		// Test items against schema(ta).
		if(is_null($this->items)){
			return TRUE;
		}
		if(is_array($this->items)){
			if($this->additionalItems===FALSE && $numItems > count($this->items)){
				$this->addError('Array contains more items than are permitted by type tuple, and additional items are disallowed.');
				return FALSE;
			}
			// Tuple-Typing
			for($i = 0; $i < count($this->items); $i++) {
				$itemSchema = $this->items[$i];
				if(!$this->validateSubSchema($itemSchema,$value[$i],(string)$i)){
					return FALSE;
				}
			}
			// Validate additionalItems
			if(is_object($this->additionalItems)) {
				for(; $i < $numItems; $i++) {
					if(!$this->validateSubSchema($this->additionalItems,$value[$i],(string)$i)){
						return FALSE;
					}
				}
			}
		}else{
			for($i=0; $i < count($value); $i++) {
				if(!$this->validateSubSchema($this->items,$value[$i],(string)$i)){
					return FALSE;
				}
			}
		}
		return TRUE;
	}

	/**
	 * Validate numeric values against minimum, maximum, exclusiveMinimum and exclusiveMaximum constraints.
	 *
	 * @param numeric $value
	 * @return boolean
	 */
	protected function validateNumber($value) {
		if(!is_numeric($value)){
			return TRUE;
		}
		if(!is_null($this->minimum) && ($value < $this->minimum || ($this->exclusiveMinimum && $value==$this->minimum))){
			$exclusive = $this->exclusiveMinimum ? ' exclusive' : '';
			$this->addError("Value of $value does not meet$exclusive minimum of $this->minimum.");
			return FALSE;
		}
		if(!is_null($this->maximum) && ($value > $this->maximum || ($this->exclusiveMaximum && $value==$this->maximum))){
			$exclusive = $this->exclusiveMinimum ? ' exclusive' : '';
			$this->addError("Value of $value exceeds maximum of $this->maximum.");
			return FALSE;
		}
		if(!is_null($this->divisibleBy) && ($value/$this->divisibleBy != floor($value/$this->divisibleBy))){
			$this->addError("Value is not divisibleBy $this->divisibleBy.");
			return FALSE;
		}
		return TRUE;
	}
	/**
	 * Validate string values against minLength, maxLength and pattern.
	 *
	 * @param string $value
	 * @return boolean
	 */
	protected function validateString($value) {
		if(gettype($value)!='string'){
			return TRUE;
		}
		// Validate length.
		$length = strlen($value);
		if(!is_null($this->minLength) && $length < $this->minLength) {
			$this->addError("String length of $length does not meet minimum length of $this->minLength.");
			return FALSE;
		}
		if(!is_null($this->maxLength) && $length > $this->maxLength) {
			$this->addError("String length of $length exceeds maximum length of $this->maxLength.");
			return FALSE;
		}
		if(!is_null($this->pattern) && !preg_match($this->pattern, $value)){
			$this->addError("String '$value' does not match regular expression pattern '$this->pattern'.");
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Validate a value against an enumeration of all possible valid values.
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	protected function validateAgainstEnum($value) {
		// We can't compare objects strictly.
		$strictMatch = !is_object($value);
		if(is_null($this->enum)){
			return TRUE;
		}
		if(!in_array($value,$this->enum, $strictMatch)){
			$this->addError("Value is not present in the enumeration.");
			return FALSE;
		}
		return TRUE;
	}
	/**
	 * Validate against a pre-defined format.
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	protected function validateAgainstFormat($value) {
		if(is_null($this->format) || gettype($value)!=='string') {
			return TRUE;
		}
		/*
			TODO Use additional format validator for custom formats.
		*/
		$valid = TRUE;
		switch ($this->format) {
			case 'date-time':
				if(!preg_match(self::DATETIME_REGEX, $value)){
					$valid = FALSE;
				}
				break;
			case 'date':
				if(!preg_match(self::DATE_REGEX, $value)){
					$valid = FALSE;
				}
				break;
			case 'time':
				if(!preg_match(self::TIME_REGEX, $value)){
					$valid = FALSE;
				}
				break;
			case 'utc-millisec':
				break;
			case 'regex':
				$regex = '/' . $value . '/';
				if(!$this->validateRegex($regex)){
					$valid = FALSE;
				}
				break;
			case 'color':
				if(!preg_match(self::CSS_COLOR_REGEX, $value)){
					$valid = FALSE;
				}
				break;
			case 'style':
				/*
					TODO Validate style format.
				*/
				break;
			case 'phone':
				if(!preg_match(self::PHONE_REGEX, $value)){
					$valid = FALSE;
				}
				break;
			case 'uri':
				if(!preg_match(self::URI_REGEX,$value)){
					$valid = FALSE;
				}
				break;
			case 'email':
				$matches = array();
				if(!preg_match(self::EMAIL_REGEX,$value, $matches)){
					$valid = FALSE;
				}
				break;
			case 'ip-address':
				$matches = array();
				if(!preg_match(self::IP_ADDRESS_REGEX,$value, $matches)){
					$valid = FALSE;
				}
				break;
			case 'ipv6':
				$matches = array();
				if(!preg_match(self::IPV6_REGEX,$value, $matches)){
					$valid = FALSE;
				}
				break;
			case 'host-name':
				$matches = array();
				if(!preg_match(self::HOSTNAME_REGEX,$value, $matches)){
					$valid = FALSE;
				}
				break;
		}
		if(!$valid){
			$this->addError("Value does not match format '$this->format'.");
		}
		return $valid;
	}

	/**
	 * Validate an object against the schema's dependencies object.
	 *
	 * @param mixed $value
	 * @return TRUE if the value is valid, FALSE otherwise.
	 */
	protected function validateObjectDependencies($value) {
		if(!is_object($value) || is_null($this->dependencies)){
			return TRUE;
		}
		$valid = TRUE;
		foreach($this->dependencies as $dependentProperty => $dependencyValue) {
			if (property_exists($value, $dependentProperty)) {
				if(is_array($dependencyValue)){
					foreach($dependencyValue as $requiredProperty) {
						if(!$this->requireDependency($value,$dependentProperty,$requiredProperty)){
							$valid = FALSE;
						}
					}
				}elseif($this->isAJSONSchema($dependencyValue)){
					if(!$this->validateSubSchema($dependencyValue, $value, 'dependencies.'.$dependentProperty)){
						$valid = FALSE;
					}
				}else{
					if(!$this->requireDependency($value,$dependentProperty,$dependencyValue)){
						$valid = FALSE;
					}
				}
			}
		}
		return $valid;
	}

	/**
	 * Require a property to be present on the given value, logging errors if it is not found.
	 *
	 * @param object $value Object on which $requiredProperty must be present.
	 * @param string $dependentProperty Name of the dependent property, for error logging.
	 * @param string $requiredProperty Name of the required property.
	 * @return boolean TRUE if the $requiredProperty is present on $value, FALSE otherwise.
	 */
	protected function requireDependency($value,$dependentProperty,$requiredProperty) {
		$valid = property_exists($value, $requiredProperty);
		if(!$valid){
			$this->addError("Dependent property $dependentProperty requires that property $requiredProperty is present.");
			$valid = FALSE;
		}
		return $valid;
	}

	/**
	 * Returns an example that matches the schema.
	 *
	 * @param int $options Exemplifying options. Currently only JSONSchema::EXAMPLE_MINIMAL and JSONSchema::EXAMPLE_MAXIMAL are allowed.
	 * @return mixed Example value matching the schema.
	 */
	public function exemplify($options=NULL) {
		if (NULL===$options) {
			$options = self::EXAMPLE_MINIMAL;
		}
		if ($this->exampleSet) {
			return $this->example;
		}
		if ($this->defaultSet) {
			return $this->default;
		}
		if ($this->enum) {
			return $this->enum[array_rand($this->enum)];
		}
		return $this->exemplifyType($this->selectTypeForExample($this->type, $options),$options);
	}

	protected function exemplifyType($type,$options) {
		if (is_object($type) and $this->isAJSONSchema($type)) {
			return $type->exemplify($options);
		}
		// Simple type example.
		switch ($type) {
			case 'string':
				return $this->exemplifyString($options);
				break;
			case 'number':
				return $this->exemplifyNumber();
				break;
			case 'integer':
				return $this->exemplifyInteger();
				break;
			case 'boolean':
				return TRUE;
				break;
			case 'object':
				return $this->exemplifyObject($options);
				break;
			case 'array':
				return $this->exemplifyArray($options);
				break;
			case 'null':
			case 'any':
			default:
				return NULL;
				break;
		}
	}

	/**
	 * Select the type to be used for an example.
	 * In a maximal example, the most complex type will be used.
	 *
	 * @param mixed $type Type or types to choose from.
	 * @param int $options Exemplify options.
	 * @return string JSON type to use in the example.
	 */
	protected function selectTypeForExample($type, $options) {
		if (!is_array($type) and $type!='any') {
			return $type;
		}

		// In order from simplest to most complex.
		$preferredTypes = array('null','boolean','integer','number','string','array','object');
		// If we're going for the maximal implementation, pick the most complex type first.
		if ($options===self::EXAMPLE_MAXIMAL) {
			$preferredTypes = array_reverse($preferredTypes);
		}

		$allowedTypes = $type=='any' ? $preferredTypes : $type;
		if ($this->disallow) {
			if (is_array($this->disallow)) {
				$allowedTypes = array_diff($allowedTypes, $this->disallow);
			}else{
				if(FALSE !== ($index = array_search($this->disallow, $allowedTypes))){
					unset($allowedTypes[$index]);
				}
			}
		}

		foreach($preferredTypes as $preferredType) {
			if (in_array($preferredType, $allowedTypes)) {
				return $preferredType;
			}
		}

		return $allowedTypes[0];
	}

	/**
	 * Create an example string.
	 *
	 * @param int $options Exemplify options.
	 * @return string Example string.
	 */
	protected function exemplifyString($options) {
		if ($this->format) {
			return $this->getFormatExample();
		}
		if($this->minLength or $this->maxLength){
			$min = NULL==$this->minLength ? 3 : $this->minLength;
			if (self::EXAMPLE_MAXIMAL===$options) {
				$max = NULL==$this->maxLength ? $min+5 : $this->maxLength;
				$numChars = rand($min,$max);
			}else{
				$numChars = $min;
			}

			$value = '';
			$charsToTake = $numChars;
			$loremIpsumLength = strlen(self::LOREM_IPSUM);
			while($charsToTake > 0){
				$chunkSize = min($loremIpsumLength, $charsToTake);
				$value .= substr(self::LOREM_IPSUM, 0, $chunkSize);
				$charsToTake -= $chunkSize;
			}
			return $value;
		}
		$simpleStrings = ['foo','bar','bat','baz'];
		return $simpleStrings[array_rand($simpleStrings)];
	}

	/**
	 * Get an example for a string with a format.
	 *
	 * @return string Example string.
	 */
	protected function getFormatExample() {
		switch ($this->format) {
			case 'date-time':
				$date = new \DateTime();
				return $date->format(\DateTime::ISO8601);
				break;
			case 'date':
				$date = new \DateTime();
				return $date->format('Y-m-d');
				break;
			case 'time':
				$date = new \DateTime();
				return $date->format('H:i:s');
				break;
			case 'utc-millisec':
				return (string)round(microtime(true) * 1000);
				break;
			case 'regex':
				return '^BOO+M$';
				break;
			case 'color':
				return '#00FFAB';
				break;
			case 'style':
				return 'th{text-align: left;}';
				break;
			case 'phone':
				return '0161 715 8954';
				break;
			case 'uri':
				return 'www.example.com/'.rand(0,1000000000);
				break;
			case 'email':
				return 'itsoneiota@example.com';
				break;
			case 'ip-address':
				return '192.0.2.0';
				break;
			case 'ipv6':
				return '2001:0db8:85a3:0042:1000:8a2e:0370:7334';
				break;
			case 'host-name':
				return 'example.com';
				break;
			case NULL:
			default:
				return NULL;
				break;
		}
	}

	/**
	 * Get minimum and maximum values for examples of integer and number types.
	 *
	 * @return array Array with minimum and maximum values.
	 */
	protected function getMinAndMax() {
		$min = NULL==$this->minimum ? 3 : ($this->exclusiveMinimum ? $this->minimum+1 : $this->minimum);
		$max = NULL==$this->maximum ? $min+100 : ($this->exclusiveMaximum ? $this->maximum-1 : $this->maximum);
		return array($min,$max);
	}

	/**
	 * Get an example number.
	 *
	 * @return number Example number.
	 */
	protected function exemplifyNumber() {
		list($min,$max) = $this->getMinAndMax();
		// Get an integer at least 1 less than the max.
		$integer = rand(ceil($min),floor($max)-1);
		// Now add a random floating point, just to make it clear that this isn't an integer.
		return $integer + (0.1 * rand(0,9));
	}

	/**
	 * Get an example integer.
	 *
	 * @return integer Example integer.
	 */
	protected function exemplifyInteger() {
		list($min,$max) = $this->getMinAndMax();
		if ($this->divisibleBy) {
			return $this->divisibleBy > $min ? $this->divisibleBy : $this->divisibleBy * floor($max/$divisibleBy);
		}
		return rand($min,$max);
	}

	/**
	 * Get an example object.
	 *
	 * @param int $options Exemplify options.
	 * @return object Example object.
	 */
	protected function exemplifyObject($options=self::EXAMPLE_MINIMAL) {
		$obj = new \stdClass();
		if (empty($this->properties) and empty($this->additionalProperties) and empty($this->patternProperties)) {
			return $obj;
		}
		// Pattern property examples.
		if ($options==self::EXAMPLE_MAXIMAL and $this->patternProperties and $this->patternPropertyExamples) {
			$obj = $this->patternPropertyExamples;
		}
		// Explicit properties.
		foreach($this->properties as $property => $schema) {
			if ($options===self::EXAMPLE_MAXIMAL or $this->propertyIsRequired($property)) {
				// It'll be turtles all the way down if we don't cut the verbosity here.
				$obj->$property = $schema->exemplify(self::EXAMPLE_MINIMAL);
			}
		}
		// Additional properties.
		if ($options==self::EXAMPLE_MAXIMAL and TRUE===$this->additionalProperties) {
			$obj->foo = 1;
			$obj->bar = 'A';
		}elseif ($options==self::EXAMPLE_MAXIMAL and $this->isAJSONSchema($this->additionalProperties)) {
			$obj->foo = $this->additionalProperties->exemplify($options);
			$obj->bar = $this->additionalProperties->exemplify($options);
		}

		return $obj;
	}

	/**
	 * Get an example array.
	 *
	 * @param int $options Exemplify options.
	 * @return array Example array.
	 */
	protected function exemplifyArray($options) {
		$arr = array();
		$min = NULL==$this->minItems ? 3 : $this->minItems;
		$max = NULL==$this->maxItems ? $min+5 : $this->maxItems;

		$numItems = self::EXAMPLE_MAXIMAL===$options ? min($max,$min+3) : $min;

		if ($this->uniqueItems and (!$this->minItems or $this->minItems <= 1)) {
			$numItems = 1;
		}

		for ($i=0; $i < $numItems;) {
			$value = $this->exemplifyArrayItem($options,$i);
			if ($this->uniqueItems and in_array($value, $arr)) {
				continue;
			}
			$arr[] = $value;
			$i++;
		}

		return $arr;
	}

	/**
	 * Get an example array item.
	 *
	 * @param int $index Array index of the item being created.
	 * @return mixed Example array item.
	 */
	protected function exemplifyArrayItem($options,$index) {
		if (is_array($this->items) and $index < count($this->items)) {
			$itemSchema = $this->items[$index];
			return $itemSchema->exemplify($options);
		}
		if (is_array($this->items) and $this->additionalItems) {
			return $this->isAJSONSchema($this->additionalItems) ? $this->additionalItems->exemplify($options) : rand(0,10000);
		}
		return $this->items ? $this->items->exemplify($options) : rand(0,10000);
	}

}
