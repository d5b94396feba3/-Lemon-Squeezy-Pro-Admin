<?php
defined( 'ABSPATH' ) || exit;

class AICA_Plugin_SimpleHTMLDOM {

    public function __construct() {
        $this->load_lib();
    }

    private function load_lib() {
        $lib_path = AICA_PLUGIN_PATH . 'vendor/simplehtmldom/simple_html_dom.php';
        if ( file_exists( $lib_path ) ) {
            require_once $lib_path;
        }
    }

    public function sync_website_content() {
        $pages = get_pages();
        $scraped_data = [];

        // Skip utility pages
        $excluded_slugs = [
            'cart', 'checkout', 'my-account', 'logout', 'login', 'register', 
            'forgot', 'password-reset', 'change-password', 'payment-success',
            'payment-cancelled', 'thank-you', 'sample-page', 'blog', 'blogs'
        ];

        foreach ( $pages as $page ) {
            if ( in_array( $page->post_name, $excluded_slugs ) ) continue;

            $url = get_permalink( $page->ID );
            $content = $this->scrape_page( $url );
            
            if ( ! empty( $content ) ) {
                $scraped_data[] = [
                    'title'   => $page->post_title,
                    'url'     => $url,
                    'content' => $content
                ];
            }
        }
        return $scraped_data;
    }

    public function scrape_page( $url ) {
        $response = wp_remote_get( $url, ['timeout' => 15] );
        if ( is_wp_error( $response ) ) return '';
    
        $html_content = wp_remote_retrieve_body( $response );
        
        // Use the library to parse
        $html = str_get_html( $html_content );
        if ( ! $html ) return '';
    
        // 1. Kill "Page Not Found" early
        $title = $html->find('title', 0) ? $html->find('title', 0)->plaintext : '';
        if (stripos($title, 'Page not found') !== false) {
            $html->clear();
            return '';
        }
    
        // 2. Remove Junk (Script, Style, Nav, Footer, GDPR)
        foreach($html->find('script, style, nav, footer, header, noscript, .gdpr, .cookie, #cookie-law-info-bar') as $item) {
            $item->outertext = ''; 
        }
    
        // 3. Get the raw text
        $text = $html->plaintext;
    
        // 4. THE NUCLEAR CLEANUP
        // Convert &nbsp; and other entities to real characters
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        
        // Replace all types of whitespace (tabs, newlines, multiple spaces, unicode spaces) with ONE space
        $text = preg_replace('/\s+/u', ' ', $text);
    
        // Clean up memory
        $html->clear();
        unset($html);
    
        return trim($text);
    }
}