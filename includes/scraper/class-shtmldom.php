<?php
defined( 'ABSPATH' ) || exit;

class AICA_Plugin_SimpleHTMLDOM {

    public function __construct() {
        $this->load_lib();
    }

    private function load_lib() {
        $lib_path = AICA_PLUGIN_PATH . 'vendor/simplehtmldom/simple_html_dom.php';
        
        if ( ! function_exists( 'str_get_html' ) ) {
            if ( file_exists( $lib_path ) ) {
                require_once $lib_path;
            }
        }
    }

    public function sync_website_content() {
        $pages = get_pages();
        $scraped_data = [];

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
        $args = [
            'timeout'    => 120,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ];

        $response = wp_remote_get( $url, $args );
        if ( is_wp_error( $response ) ) return '';
    
        $html_content = wp_remote_retrieve_body( $response );
        
        if ( strlen( $html_content ) > 10000000 ) {
            return '';
        }

        if ( ! function_exists( 'str_get_html' ) ) {
            return '';
        }

        $html = str_get_html( $html_content );
        if ( ! $html ) return '';
    
        $title_node = $html->find('title', 0);
        $title = $title_node ? $title_node->plaintext : '';
        
        if (stripos($title, 'Page not found') !== false || stripos($title, '404') !== false) {
            $html->clear();
            return '';
        }
    
        foreach($html->find('script, style, nav, footer, header, noscript, .gdpr, .cookie, #cookie-law-info-bar') as $item) {
            $item->outertext = ''; 
        }
    
        $text = $html->plaintext;
        
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);
        
        $html->clear();
        unset($html);
    
        return trim($text);
    }
}