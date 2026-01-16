<?php
defined( 'ABSPATH' ) || exit;
class AICA_Plugin_Smalot_Parser {

    private $plugin_slug;

    public function __construct($plugin_slug) {
        $this->plugin_slug = $plugin_slug;
    }

    public function extract_text_from_pdf($filename) {
        if (!file_exists($filename)) {
            return "";
        }
    
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf    = $parser->parseFile($filename);
            
            $text   = $pdf->getText();
            
            $text = preg_replace('/[ \t]+/', ' ', $text);
            $text = preg_replace('/\n\s*\n/', "\n", $text);
            
            return mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        } catch (\Exception $e) {
            error_log("AICA PDF Parse Error: " . $e->getMessage());
            return "Note: The attached PDF knowledge base could not be parsed.";
        }
    }

}
