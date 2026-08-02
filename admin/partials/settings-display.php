<?php
/**
 * Settings Page Display
 *
 * @package    WP_SEO_Blog_Automater
 * @author     Codezela Technologies
 * @since      1.0.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;
?>
<div class="wp-seo-wrap">
    <div class="wp-seo-header">
        <div>
            <h1><?php echo esc_html_x( 'Settings', 'Settings page title', 'wp-seo-blog-automater' ); ?></h1>
            <p class="wp-seo-subtitle"><?php esc_html_e( 'Configure your AI content generation settings', 'wp-seo-blog-automater' ); ?></p>
        </div>
        <?php if ( file_exists( WP_SEO_AUTOMATER_PATH . 'images/logo.png' ) ) : ?>
            <div class="wp-seo-branding">
                <img src="<?php echo esc_url( WP_SEO_AUTOMATER_URL . 'images/logo.png' ); ?>" alt="<?php esc_attr_e( 'Codezela Technologies', 'wp-seo-blog-automater' ); ?>" class="wp-seo-logo">
            </div>
        <?php endif; ?>
    </div>

    <?php if ( ! empty( $settings_notices ) ) : ?>
        <?php foreach ( $settings_notices as $notice ) : ?>
            <div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible">
                <p><?php echo esc_html( $notice['message'] ); ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field( 'wp_seo_automater_settings_save' ); ?>
        
        <div class="wp-seo-card">
            <h2><?php esc_html_e( 'API Configuration', 'wp-seo-blog-automater' ); ?></h2>
            <div class="wp-seo-form-group">
                <label class="wp-seo-label" for="gemini_api_key">
                    <?php esc_html_e( 'Gemini API Key', 'wp-seo-blog-automater' ); ?>
                    <span class="required">*</span>
                </label>
                <input 
                    type="password" 
                    id="gemini_api_key" 
                    name="gemini_api_key" 
                    class="wp-seo-input" 
                    value="<?php echo esc_attr( $api_key ); ?>" 
                    placeholder="<?php esc_attr_e( 'Enter your Gemini API Key here...', 'wp-seo-blog-automater' ); ?>"
                    required
                >
                <p class="description">
                    <?php 
                    printf(
                        /* translators: %s: URL to Google AI Studio */
                        esc_html__( 'Get your API key from %s', 'wp-seo-blog-automater' ),
                        '<a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener">' . esc_html__( 'Google AI Studio', 'wp-seo-blog-automater' ) . '</a>'
                    );
                    ?>
                </p>
            </div>

            <div class="wp-seo-form-group">
                <label class="wp-seo-label" for="gemini_model_id">
                    <?php esc_html_e( 'Gemini Model ID', 'wp-seo-blog-automater' ); ?>
                </label>
                <input 
                    type="text" 
                    id="gemini_model_id" 
                    name="gemini_model_id" 
                    class="wp-seo-input" 
                    value="<?php echo esc_attr( $model_id ); ?>" 
                    placeholder="gemini-pro-latest"
                >
                <p class="description">
                    <?php esc_html_e( 'Used for article generation. Gemini 2.5 Flash is the recommended general-purpose default for new installs.', 'wp-seo-blog-automater' ); ?>
                </p>
            </div>

            <div class="wp-seo-form-group">
                <label class="wp-seo-label" for="image_alt_model_id">
                    <?php esc_html_e( 'Image SEO Model ID', 'wp-seo-blog-automater' ); ?>
                </label>
                <input
                    type="text"
                    id="image_alt_model_id"
                    name="image_alt_model_id"
                    class="wp-seo-input"
                    value="<?php echo esc_attr( $image_alt_model ); ?>"
                    placeholder="gemini-2.5-flash"
                >
                <p class="description">
                    <?php esc_html_e( 'Used only for Media Library image analysis. The default Gemini 2.5 Flash accepts image input and returns fast, high-quality text using the same Gemini API key above.', 'wp-seo-blog-automater' ); ?>
                </p>
            </div>

            <div class="wp-seo-form-group">
                <label class="wp-seo-label" for="image_alt_site_context">
                    <?php esc_html_e( 'Website and Brand Context for Image SEO', 'wp-seo-blog-automater' ); ?>
                </label>
                <textarea
                    id="image_alt_site_context"
                    name="image_alt_site_context"
                    class="wp-seo-textarea"
                    rows="5"
                    maxlength="1200"
                    placeholder="<?php esc_attr_e( 'Optional: describe your business, audience, products, service area, and confirmed brand names or logos...', 'wp-seo-blog-automater' ); ?>"
                ><?php echo esc_textarea( $image_alt_site_context ); ?></textarea>
                <p class="description">
                    <?php esc_html_e( 'Sent only with AI image-text requests together with the site name, tagline, URL, and attached post context. It helps Gemini identify visible logos and products accurately; do not include secrets or private customer data.', 'wp-seo-blog-automater' ); ?>
                </p>
            </div>

            <div class="wp-seo-form-group">
                <label class="wp-seo-label" for="unsplash_key">
                    <?php esc_html_e( 'Unsplash Access Key', 'wp-seo-blog-automater' ); ?>
                </label>
                <input 
                    type="password" 
                    id="unsplash_key" 
                    name="unsplash_key" 
                    class="wp-seo-input" 
                    value="<?php echo esc_attr( $unsplash_key ); ?>" 
                    placeholder="<?php esc_attr_e( 'Enter Unsplash Access Key...', 'wp-seo-blog-automater' ); ?>"
                >
                <p class="description">
                    <?php 
                    printf(
                        /* translators: %s: URL to Unsplash Developers */
                        esc_html__( 'Required for automatic image fetching. Get it from %s', 'wp-seo-blog-automater' ),
                        '<a href="https://unsplash.com/developers" target="_blank" rel="noopener">' . esc_html__( 'Unsplash Developers', 'wp-seo-blog-automater' ) . '</a>'
                    );
                    ?>
                </p>
            </div>
        </div>

        <div class="wp-seo-card">
            <h2><?php esc_html_e( 'Search Engine Submission', 'wp-seo-blog-automater' ); ?></h2>

            <div class="wp-seo-form-group">
                <label class="wp-seo-label" for="indexnow_key">
                    <?php esc_html_e( 'IndexNow Key', 'wp-seo-blog-automater' ); ?>
                </label>
                <input
                    type="text"
                    id="indexnow_key"
                    name="indexnow_key"
                    class="wp-seo-input"
                    value="<?php echo esc_attr( $indexnow_key ); ?>"
                    placeholder="<?php esc_attr_e( 'Enter or generate an IndexNow key...', 'wp-seo-blog-automater' ); ?>"
                >
                <p class="description">
                    <?php esc_html_e( 'Used for the bulk "Submit to IndexNow" action on Posts and Pages. The plugin serves the verification file for this key automatically, so you do not need to upload a physical .txt file yourself.', 'wp-seo-blog-automater' ); ?>
                </p>
                <?php if ( ! empty( $indexnow_key_file_url ) ) : ?>
                    <p class="description">
                        <?php
                        printf(
                            /* translators: %s: IndexNow verification file URL */
                            esc_html__( 'Verification file URL: %s', 'wp-seo-blog-automater' ),
                            esc_url( $indexnow_key_file_url )
                        );
                        ?>
                    </p>
                <?php endif; ?>
            </div>

            <div class="wp-seo-form-group">
                <label class="wp-seo-label" for="google_property">
                    <?php esc_html_e( 'Google Search Console Property', 'wp-seo-blog-automater' ); ?>
                </label>
                <input
                    type="text"
                    id="google_property"
                    name="google_property"
                    class="wp-seo-input"
                    value="<?php echo esc_attr( $google_property ); ?>"
                    placeholder="https://example.com/ or sc-domain:example.com"
                >
                <p class="description">
                    <?php esc_html_e( 'Required for the Google bulk actions. Use a URL-prefix property with a trailing slash or a Domain property in the format sc-domain:example.com.', 'wp-seo-blog-automater' ); ?>
                </p>
            </div>

            <div class="wp-seo-form-group">
                <label class="wp-seo-label" for="google_service_account_json">
                    <?php esc_html_e( 'Google Service Account JSON', 'wp-seo-blog-automater' ); ?>
                </label>
                <textarea
                    id="google_service_account_json"
                    name="google_service_account_json"
                    class="wp-seo-textarea"
                    rows="12"
                    placeholder="<?php esc_attr_e( 'Paste the full Google Cloud service account JSON here...', 'wp-seo-blog-automater' ); ?>"
                ><?php echo esc_textarea( $google_service_account_json ); ?></textarea>
                <p class="description">
                    <?php esc_html_e( 'Required for "Resubmit Sitemap to Google" and "Check Google Index Status". Add the service account email as an owner or full user on the matching Search Console property.', 'wp-seo-blog-automater' ); ?>
                </p>
                <?php if ( ! empty( $google_service_account_email ) ) : ?>
                    <p class="description">
                        <?php
                        printf(
                            /* translators: %s: service account email */
                            esc_html__( 'Configured service account email: %s', 'wp-seo-blog-automater' ),
                            esc_html( $google_service_account_email )
                        );
                        ?>
                    </p>
                <?php endif; ?>
            </div>

            <div class="wp-seo-form-group">
                <label class="wp-seo-label" for="google_sitemap_urls">
                    <?php esc_html_e( 'Google Sitemap URLs', 'wp-seo-blog-automater' ); ?>
                </label>
                <textarea
                    id="google_sitemap_urls"
                    name="google_sitemap_urls"
                    class="wp-seo-textarea"
                    rows="4"
                    placeholder="<?php esc_attr_e( "One sitemap URL per line. Leave blank to auto-detect /sitemap_index.xml, /wp-sitemap.xml, or /sitemap.xml.", 'wp-seo-blog-automater' ); ?>"
                ><?php echo esc_textarea( $google_sitemap_urls ); ?></textarea>
                <p class="description">
                    <?php esc_html_e( 'Optional override for the bulk "Resubmit Sitemap to Google" action. If left blank, the plugin will try the standard sitemap endpoints automatically.', 'wp-seo-blog-automater' ); ?>
                </p>
            </div>
        </div>

        <div class="wp-seo-card">
            <h2><?php esc_html_e( 'SEO Plugin Integration', 'wp-seo-blog-automater' ); ?></h2>
            <div class="wp-seo-form-group">
                <label class="wp-seo-label" for="seo_plugin">
                    <?php esc_html_e( 'Active SEO Plugin', 'wp-seo-blog-automater' ); ?>
                </label>
                <select id="seo_plugin" name="seo_plugin" class="wp-seo-input">
                    <option value="auto" <?php selected( $seo_plugin, 'auto' ); ?>>
                        <?php esc_html_e( 'Auto Detect (Recommended)', 'wp-seo-blog-automater' ); ?>
                    </option>
                    <option value="yoast" <?php selected( $seo_plugin, 'yoast' ); ?>>
                        <?php esc_html_e( 'Yoast SEO', 'wp-seo-blog-automater' ); ?>
                    </option>
                    <option value="rankmath" <?php selected( $seo_plugin, 'rankmath' ); ?>>
                        <?php esc_html_e( 'Rank Math', 'wp-seo-blog-automater' ); ?>
                    </option>
                </select>
                <p class="description">
                    <?php esc_html_e( 'Select which SEO plugin to populate metadata for. Auto Detect will check your active plugins automatically.', 'wp-seo-blog-automater' ); ?>
                </p>
            </div>
        </div>

        <div class="wp-seo-card">
            <h2><?php esc_html_e( 'Master Prompt Configuration', 'wp-seo-blog-automater' ); ?></h2>
            <div class="wp-seo-form-group">
                <label class="wp-seo-label" for="master_prompt">
                    <?php esc_html_e( 'Master System Prompt', 'wp-seo-blog-automater' ); ?>
                </label>
                <textarea 
                    id="master_prompt" 
                    name="master_prompt" 
                    class="wp-seo-textarea large"
                    rows="20"
                ><?php echo esc_textarea( $master_prompt ); ?></textarea>
                <p class="description">
                    <?php esc_html_e( 'This prompt defines the AI persona, voice, and content rules. Customize carefully to match your brand requirements.', 'wp-seo-blog-automater' ); ?>
                </p>
            </div>
        </div>

        <div class="wp-seo-actions">
            <button type="submit" name="wp_seo_automater_save_settings" class="wp-seo-btn wp-seo-btn-primary">
                <span class="dashicons dashicons-yes"></span>
                <?php esc_html_e( 'Save Settings', 'wp-seo-blog-automater' ); ?>
            </button>

            <button type="submit" name="wp_seo_automater_generate_indexnow_key" class="wp-seo-btn wp-seo-btn-secondary">
                <span class="dashicons dashicons-admin-links"></span>
                <?php esc_html_e( 'Generate IndexNow Key', 'wp-seo-blog-automater' ); ?>
            </button>

            <button 
                type="submit" 
                name="wp_seo_automater_reset_prompt" 
                class="wp-seo-btn wp-seo-btn-secondary" 
                onclick="return confirm('<?php echo esc_js( __( 'Are you sure? This will overwrite your current Master Prompt with the default one.', 'wp-seo-blog-automater' ) ); ?>');"
            >
                <span class="dashicons dashicons-image-rotate"></span>
                <?php esc_html_e( 'Reset to Default Prompt', 'wp-seo-blog-automater' ); ?>
            </button>
        </div>
    </form>
    
    <div class="wp-seo-footer">
        <p>
            <?php 
            printf(
                /* translators: %s: Codezela Technologies link */
                esc_html__( 'Powered by %s', 'wp-seo-blog-automater' ),
                '<a href="https://codezela.com" target="_blank" rel="noopener"><strong>' . esc_html__( 'Codezela Technologies', 'wp-seo-blog-automater' ) . '</strong></a>'
            );
            ?>
        </p>
    </div>
</div>
