<?php

/**
 * Plugin Settings
 */

namespace SWCRS\Setup;

/**
 * Class Settings
 */
class Settings
{
    /**
     * Settings constructor.
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'wc_review_summarizer_menu']);
        add_action('admin_init', [$this, 'wc_review_summarizer_settings']);
    }

    /**
     * Add a submenu under WooCommerce
     * @return void
     */
    public function wc_review_summarizer_menu()
    {
        add_submenu_page(
            'woocommerce',
            'Review Summarizer',
            'Review Summarizer',
            'manage_options',
            'wc-review-summarizer',
            [$this, 'wc_review_summarizer_page']
        );
    }

    // API key input field
    function wc_review_summarizer_api_key_callback()
    {
        $api_key = get_option('wc_review_summarizer_api_key');
        echo '<input type="text" id="wc_review_summarizer_api_key" name="wc_review_summarizer_api_key" value="' . esc_attr($api_key) . '" />';
    }

    /**
     * Display the plugin settings page
     * @return void
     */
    public function wc_review_summarizer_page()
    {
        include_once(plugin_dir_path(__FILE__)) . '../../templates/settings-page.php';
    }

    /**
     * Register the plugin settings
     * @return void
     */
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
}
