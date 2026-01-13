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
        
        $html = str_get_html( $html_content );
        if ( ! $html ) return '';
    
        $title = $html->find('title', 0) ? $html->find('title', 0)->plaintext : '';
        if (stripos($title, 'Page not found') !== false) {
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