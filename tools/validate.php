<?php
include __DIR__.'/../vendor/autoload.php';
use \itsoneiota\json\JSONSchemaBuilder;

if(count($argv) != 3){
    echo "Usage: php validate.php path/to/schema.json path/to/document.json";
    die();
}

$schemaFile = $argv[1];
$document = $argv[2];

$builder = new JSONSchemaBuilder();
$schema = $builder->build($schemaFile);
$input = json_decode(file_get_contents($document));

$success = $schema->validate($input);
if($success){
    echo "Document at $document matches schema at $schemaFile.";
}else{
    echo "FAIL\n".json_encode($schema->getErrors(), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
}
