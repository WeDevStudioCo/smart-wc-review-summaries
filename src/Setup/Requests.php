<?php
/**
 * Requests class
 */

namespace SWCRS\Setup;

class Requests {
    public function __construct()
    {
        add_action('wp_ajax_get_product_summary', [$this, 'get_product_summary']);
        add_action('wp_ajax_nopriv_get_product_summary', [$this, 'get_product_summary']);
        add_action('woocommerce_before_single_product_summary', [$this, 'wc_review_summarizer_display_summary'], 15);
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

        $reviews = $this->wc_review_summarizer_get_reviews($product->get_id());
        if (empty($reviews)) {
            echo '<p>No reviews to summarize.</p>';
            return;
        }

        $summary = $this->wc_review_summarizer_summarize($reviews);
        echo '<h3>Review Summary</h3>';
        echo '<p>' . esc_html($summary) . '</p>';
    }

}