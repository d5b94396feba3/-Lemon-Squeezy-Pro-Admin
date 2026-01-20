<?php
/**
 * PDF Text Extraction using Smalot Library
 */
defined( 'ABSPATH' ) || exit;

class AICA_Plugin_Smalot_Parser {

    private $plugin_slug;

    public function __construct($plugin_slug) {
        $this->plugin_slug = $plugin_slug;
    }

    public function extract_text_from_pdf($filename) {
        if (!file_exists($filename) || !is_readable($filename)) {
            return "";
        }
    
        if ( ! class_exists( '\\Smalot\\PdfParser\\Parser' ) ) {
            return __( 'Note: PDF Parser library is missing.', 'aica-plugin' );
        }

        try {
            @set_time_limit(100);

            $parser = new \Smalot\PdfParser\Parser();
            $pdf    = $parser->parseFile($filename);
            
            $text   = $pdf->getText();
            
            $text = preg_replace('/[ \t]+/', ' ', $text);
            $text = preg_replace('/\n\s*\n/', "\n", $text);
            
            $clean_text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
            
            return trim($clean_text);

        } catch (\Exception $e) {
            error_log("AICA PDF Parse Error: " . $e->getMessage());
            return __( 'Note: The attached PDF knowledge base could not be parsed.', 'aica-plugin' );
        }
    }
}