<?php
/**
 * The template for the settings page
 *
 * @package WC_Review_Summarizer
 */
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