<?php
namespace itsoneiota\json;
/**
 * Autoloader for JSON Schemas.
 *
 * Given a base URI and a root directory for local schema files,
 * the autoloader will resolve requests for a schema URI and return the contents
 * of the matching schema either from local files or from the web, as necessary.
 *
 * It's worth noting that the autoloader doesn't cache
 */
class JSONSchemaAutoLoader implements JSONSchemaLoader {

	protected $baseURI;
	protected $baseURIParts;
	protected $schemaRoot;
	protected $baseHost;
	protected $basePath;
	protected $basePathLength;

	protected $URIMap = [];

	protected $fetcher = NULL;

    /**
      * Constructor
      *
      * @param string $baseURI Base URI of for local schemas.
      * @param string $schemaRoot Root directory for local schemas, corresponding to $baseURI.
	  * @param Fetcher $fetcher Object wrapper for file_get_contents. If supplied, it will be used to fetch URIs above the base.
      *
      * @example Given $baseURI = "http://example.com/schemas" and $schemaRoot = "/var/www/schemas", the autoloader would resolve
      * a schema URI of "http://example.com/schemas/foo/bar/bat.json" to "/var/www/schemas/foo/bar/bat.json".
     */
	function __construct($baseURI, $schemaRoot, Fetcher $fetcher=NULL) {
		$this->baseURI = $baseURI;
		$this->fetcher = $fetcher;

		$this->baseURIParts = parse_url($baseURI);
		$this->baseHost = $this->baseURIParts['host'];
		$this->basePath = $this->baseURIParts['path'];
        $this->basePathParts = explode('/', $this->basePath);
		$this->basePathLength = strlen($this->basePath);

		$this->schemaRoot = $schemaRoot;
	}

    /**
      * Explicitly map a URI to a file path.
      *
      * @param string $URI The schema URI to be mapped.
      * @param string $filePath The local location of the schema file.
     */
	public function mapURIToFile($URI, $filePath) {
		$this->URIMap[$URI] = $filePath;
	}

    /**
      * Load a schema at the given URI.
      *
      * @param string $URI
      * @return string Content of the schema file, or NULL if not found.
     */
	public function loadSchema($URI) {
		if (array_key_exists($URI, $this->URIMap)) {
			return file_get_contents($this->URIMap[$URI]);
		}

		$URIParts = parse_url($URI);
    	$absoluteURI = http_build_url($this->baseURI, $URIParts, HTTP_URL_JOIN_PATH);
    	$absoluteParts = parse_url($absoluteURI);
    	// Is the URI local?
		$hostsMatch = $absoluteParts['host'] == $this->baseHost;
		$pathsMatch = substr_compare($absoluteParts['path'], $this->basePath, 0, $this->basePathLength)==0;
        $URIIsLocal = $hostsMatch && $pathsMatch;

        // Remove the common parts of the path, leaving the path to the schema relative to the schema root.
        $subPath = substr($absoluteParts['path'], $this->basePathLength);
        return $URIIsLocal ? $this->loadFromLocalFile($subPath) : $this->loadFromAbsoluteURI($absoluteURI);
	}

	/**
	 * Given a relatively local path, return the contents of the corresponding local file.
	 *
	 * @param string $path
	 * @param string $extension
	 */
	protected function loadFromLocalFile($path, $extension=NULL) {
		$pathParts = explode('/', $path);

		$fileName = end($pathParts);
		$extension = '.json';
		if(strstr($fileName, '.')){
			$extension = NULL;
		}

		$filePath = rtrim($this->schemaRoot, '/') . DIRECTORY_SEPARATOR . $path . $extension;
		if(!is_readable($filePath)){
			return NULL;
		}
		return file_get_contents($filePath);
    }

	/**
	 * Fetch schema contents from an absolute URI.
	 *
	 * @param $URI
	 * @return string Contents of schema at the given URI.
	 * @throws \RuntimeException if no $fetcher was set in the constructor.
	 */
	protected function loadFromAbsoluteURI($URI){
		if(NULL === $this->fetcher){
			throw new \RuntimeException('Cannot retrieve absolute URI. Fetcher has not been configured.');
		}
		return $this->fetcher->fetch($URI);
	}

}
