<?php
namespace itsoneiota\json;
/**
 * Interface for classes that load JSON schemata.
 *
 **/
interface JSONSchemaLoader {
	/**
	 * Load the specified schema.
	 *
	 * @param string $ref Name of, or path to the schema to be loaded.
	 * @return string Schema contents as JSON, or NULL if $ref cannot be resolved.
	 */
	public function loadSchema($ref);
}
