<?php
/**
 * Plugin Name: Smart WC Review Summaries
 * Description: Enhance your WooCommerce store by using advanced AI technology to summarize product reviews.
 * Author: WeDev
 * 
 * Version: 1.0
 * @package SWCRS
 * 
 */

 /**
 * Autoloader PSR-4 for composer dependencies
 */
require_once __DIR__ . '/vendor/autoload.php';

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load plugin classes
new SWCRS\Setup\Settings();
new SWCRS\Setup\Requests();

// API key input field
function wc_review_summarizer_api_key_callback()
{
    $api_key = get_option('wc_review_summarizer_api_key');
    echo '<input type="text" id="wc_review_summarizer_api_key" name="wc_review_summarizer_api_key" value="' . esc_attr($api_key) . '" />';
}


/* TODO: Ajax request to get product summary
function get_product_summary() {
    $api_key = get_option('wc_review_summarizer_api_key');
    if (!$api_key) {
        return 'Error: No API key set. Please configure the API key in the plugin settings.';
    }
    $url = 'https://api.openai.com/v1/engines/davinci-codex/completions';
    $data = array('prompt' => 'Translate these reviews into a summary:', 'max_tokens' => 150);

    $response = wp_remote_post($url, array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ),
        'body' => json_encode($data),
        'timeout' => 30,
    ));

    if (wp_remote_retrieve_response_code($response) === 200) {
        $body = json_decode(wp_remote_retrieve_body($response), true);
        echo $body['choices'][0]['message']['content'];
    } else {
        echo 'Error: ' . wp_remote_retrieve_body($response);
    }

    wp_die(); // this is required to terminate immediately and return a proper response
}
add_action('wp_ajax_get_product_summary', 'get_product_summary');
add_action('wp_ajax_nopriv_get_product_summary', 'get_product_summary');
*/

// Register the product summary block
function register_product_summary_block()
{
    wp_register_script(
        'product-summary-block',
        plugins_url('block.js', __FILE__),
        array('wp-blocks', 'wp-element', 'wp-editor'),
        true
    );

    register_block_type('my-plugin/product-summary', array(
        'editor_script' => 'product-summary-block',
        'render_callback' => 'render_product_summary_block',
    ));
}
add_action('init', 'register_product_summary_block');
