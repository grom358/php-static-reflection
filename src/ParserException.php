<?php
namespace StaticReflection;

class ParserException extends \Exception {
    /**
     * @param string $filename
     * @param int $lineNo
     * @param string $message
     */
    public function __construct($filename, $lineNo, $message) {
        $details = 'Error at line ' . $lineNo;
        if ($filename) {
            $details .= ' in file ' . $filename;
        }
        $message = "$details: $message";
        parent::__construct($message);
    }
}
