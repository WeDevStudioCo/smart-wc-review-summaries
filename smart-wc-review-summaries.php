<?php
/*
Plugin Name: Smart WC Review Summaries
Description: Enhance your WooCommerce store by using advanced AI technology to summarize product reviews.
Author: Federico Ruffa
*/

require 'vendor/autoload.php';

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add a submenu under WooCommerce
function wc_review_summarizer_menu()
{
    add_submenu_page(
        'woocommerce',
        'Review Summarizer',
        'Review Summarizer',
        'manage_options',
        'wc-review-summarizer',
        'wc_review_summarizer_page'
    );
}
add_action('admin_menu', 'wc_review_summarizer_menu');

// Display the plugin settings page
function wc_review_summarizer_page()
{
?>
    <div class="wrap">
        <h1>Better WC Reviews</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('wc_review_summarizer_options');
            do_settings_sections('wc-review-summarizer');
            submit_button();
            ?>
        </form>
    </div>
<?php
}

// Register and define the settings
function wc_review_summarizer_settings()
{
    register_setting('wc_review_summarizer_options', 'wc_review_summarizer_api_key');

    add_settings_section(
        'wc_review_summarizer_main',
        'Main Settings',
        null,
        'wc-review-summarizer'
    );

    add_settings_field(
        'wc_review_summarizer_api_key',
        'API Key',
        'wc_review_summarizer_api_key_callback',
        'wc-review-summarizer',
        'wc_review_summarizer_main'
    );
}
add_action('admin_init', 'wc_review_summarizer_settings');

// API key input field
function wc_review_summarizer_api_key_callback()
{
    $api_key = get_option('wc_review_summarizer_api_key');
    echo '<input type="text" id="wc_review_summarizer_api_key" name="wc_review_summarizer_api_key" value="' . esc_attr($api_key) . '" />';
}

// Fetch product reviews
function wc_review_summarizer_get_reviews($product_id)
{
    $args = array(
        'post_id' => $product_id,
        'status' => 'approve',
        'post_type' => 'product',
    );

    $comments = get_comments($args);

    $reviews = array();
    foreach ($comments as $comment) {
        $reviews[] = $comment->comment_content;
    }

    return $reviews;
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

// Summarize reviews using OpenAI ChatGPT
function wc_review_summarizer_summarize($reviews)
{
    // Define a unique transient name for this set of reviews
    $transient_name = 'review_summary_' . md5(json_encode($reviews));

    // Try to get the summary from the transient
    $summary = get_transient($transient_name);

    // If the summary is not in the transient, generate it
    if ($summary === false) {
        $api_key = get_option('wc_review_summarizer_api_key');
        if (!$api_key) {
            return 'Error: No API key set. Please configure the API key in the plugin settings.';
        }

        $url = 'https://api.openai.com/v1/chat/completions';

        $messages = array(
            array('role' => 'system', 'content' => 'You are a helpful assistant.'),
            array('role' => 'user', 'content' => 'Summarize the positive aspects of the following reviews: ' . implode(' ', $reviews))
        );

        $data = array(
            'model' => 'gpt-4-turbo', // Use the appropriate model name
            'messages' => $messages,
            'max_tokens' => 150,
        );

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ),
            'body' => json_encode($data),
            'timeout' => 30, // Increase timeout to 30 seconds
        ));


        // Debug: Log the API response
        error_log('API Response: ' . print_r($response, true));

        if (wp_remote_retrieve_response_code($response) === 200) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            $summary = $body['choices'][0]['message']['content'];

            // Store the summary in the transient for 12 hours
            set_transient($transient_name, $summary, 12 * HOUR_IN_SECONDS);
        } else {
            $summary = 'Error: ' . wp_remote_retrieve_body($response);
        }
    }
    return $summary;
}

// Display summarized reviews on the product page
function wc_review_summarizer_display_summary()
{

    global $product;

    if (!$product || !is_product()) {
        return;
    }

    $reviews = wc_review_summarizer_get_reviews($product->get_id());
    if (empty($reviews)) {
        echo '<p>No reviews to summarize.</p>';
        return;
    }

    $summary = wc_review_summarizer_summarize($reviews);
    echo '<h3>Review Summary</h3>';
    echo '<p>' . esc_html($summary) . '</p>';
}

add_action('woocommerce_before_single_product_summary', 'wc_review_summarizer_display_summary', 15);

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
