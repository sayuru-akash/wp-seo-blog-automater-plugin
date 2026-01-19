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
                    <?php esc_html_e( 'Model options: gemini-pro-latest, gemini-1.5-pro, gemini-2.0-flash-exp', 'wp-seo-blog-automater' ); ?>
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
