<?php
namespace itsoneiota\json;

/**
 * Simple wrapper for file_get_contents(), to aid testing.
 */
class Fetcher {
    public function fetch($URI){
        return file_get_contents($URI);
    }
}
