<?php
/**
 * Generator Page Display
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
            <h1><?php echo esc_html_x( 'Article Generator', 'Generator page title', 'wp-seo-blog-automater' ); ?></h1>
            <p class="wp-seo-subtitle"><?php esc_html_e( 'Create SEO-optimized content powered by AI', 'wp-seo-blog-automater' ); ?></p>
        </div>
        <?php if ( file_exists( WP_SEO_AUTOMATER_PATH . 'images/logo.png' ) ) : ?>
            <div class="wp-seo-branding">
                <img src="<?php echo esc_url( WP_SEO_AUTOMATER_URL . 'images/logo.png' ); ?>" alt="<?php esc_attr_e( 'Codezela Technologies', 'wp-seo-blog-automater' ); ?>" class="wp-seo-logo">
            </div>
        <?php endif; ?>
    </div>

    <div class="wp-seo-card">
        <h2><?php esc_html_e( 'New Article Details', 'wp-seo-blog-automater' ); ?></h2>
        <div class="wp-seo-form-group">
             <label class="wp-seo-label" for="article_title">
                 <?php esc_html_e( 'Article Concept / Working Title', 'wp-seo-blog-automater' ); ?>
                 <span class="required">*</span>
             </label>
             <input 
                 type="text" 
                 id="article_title" 
                 class="wp-seo-input" 
                 placeholder="<?php esc_attr_e( 'e.g. Benefits of Titanium Frames for Scottsdale Golfers', 'wp-seo-blog-automater' ); ?>"
                 required
             >
        </div>

        <div class="wp-seo-form-group">
             <label class="wp-seo-label" for="article_keywords">
                 <?php esc_html_e( 'Target Keywords (Comma separated)', 'wp-seo-blog-automater' ); ?>
                 <span class="required">*</span>
             </label>
             <input 
                 type="text" 
                 id="article_keywords" 
                 class="wp-seo-input" 
                 placeholder="<?php esc_attr_e( 'e.g. luxury eyewear scottsdale, titanium glasses, lightweight frames', 'wp-seo-blog-automater' ); ?>"
                 required
             >
        </div>

        <div class="wp-seo-form-group">
            <button id="btn-generate" type="button" class="wp-seo-btn wp-seo-btn-primary">
                <span class="wp-seo-loader"></span>
                <span class="dashicons dashicons-edit"></span>
                <span class="btn-text"><?php esc_html_e( 'Generate Article', 'wp-seo-blog-automater' ); ?></span>
            </button>
            <p class="description">
                <?php esc_html_e( 'This may take 30-60 seconds. Please wait...', 'wp-seo-blog-automater' ); ?>
            </p>
        </div>
    </div>

    <!-- Results Area (Hidden by default) -->
    <div id="generation-results" class="wp-seo-hidden">
        <div class="wp-seo-card">
            <h2><?php esc_html_e( 'Generated Content Preview', 'wp-seo-blog-automater' ); ?></h2>
            
            <div class="wp-seo-form-group">
                <label class="wp-seo-label"><?php esc_html_e( 'Post Title (H1)', 'wp-seo-blog-automater' ); ?></label>
                <input type="text" id="result_title" class="wp-seo-input">
            </div>

            <div class="wp-seo-form-group">
                <label class="wp-seo-label"><?php esc_html_e( 'SEO Meta Title', 'wp-seo-blog-automater' ); ?></label>
                <input type="text" id="result_meta_title" class="wp-seo-input">
            </div>

            <div class="wp-seo-form-group">
                <label class="wp-seo-label"><?php esc_html_e( 'Meta Description', 'wp-seo-blog-automater' ); ?></label>
                <textarea id="result_meta_desc" class="wp-seo-textarea" rows="3"></textarea>
            </div>

            <!-- Image Preview -->
            <div class="wp-seo-form-group">
                <label class="wp-seo-label"><?php esc_html_e( 'Featured Image (Unsplash)', 'wp-seo-blog-automater' ); ?></label>
                <div id="image-preview-container" style="margin-bottom: 10px;">
                    <img id="result_image_preview" src="" style="max-width: 100%; height: auto; display: none; border-radius: 8px; border: 1px solid #ddd;">
                    <p id="result_image_credit" style="font-size: 12px; color: #666; font-style: italic;"></p>
                </div>
                <input type="hidden" id="result_image_url">
            </div>

            <div class="wp-seo-form-group">
                <label class="wp-seo-label" for="result_slug">
                    <?php esc_html_e( 'URL Slug', 'wp-seo-blog-automater' ); ?>
                </label>
                <input type="text" id="result_slug" class="wp-seo-input">
                <p class="description"><?php esc_html_e( 'Auto-generated. Edit if needed.', 'wp-seo-blog-automater' ); ?></p>
            </div>

            <!-- Hidden Schema Field -->
            <input type="hidden" id="result_schema">

            <div class="wp-seo-form-group">
                <label class="wp-seo-label"><?php esc_html_e( 'Content (HTML)', 'wp-seo-blog-automater' ); ?></label>
                <textarea id="result_content" class="wp-seo-textarea large" style="min-height: 600px;"></textarea>
            </div>

            <div class="wp-seo-actions">
                <button id="btn-publish" type="button" class="wp-seo-btn wp-seo-btn-primary">
                    <span class="dashicons dashicons-wordpress"></span>
                    <?php esc_html_e( 'Publish to WordPress', 'wp-seo-blog-automater' ); ?>
                </button>
                <button id="btn-discard" type="button" class="wp-seo-btn wp-seo-btn-secondary">
                    <span class="dashicons dashicons-dismiss"></span>
                    <?php esc_html_e( 'Discard', 'wp-seo-blog-automater' ); ?>
                </button>
                <span id="publish-message" class="wp-seo-message"></span>
            </div>
        </div>
    </div>
    
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
