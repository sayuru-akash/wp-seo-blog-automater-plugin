<?php
/**
 * System Info Page Display
 *
 * @package    WP_SEO_Blog_Automater
 * @author     Codezela Technologies
 * @since      1.0.6
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Get plugin data
$plugin_data = get_plugin_data( WP_SEO_AUTOMATER_FILE );
$current_version = WP_SEO_AUTOMATER_VERSION;

// Check for latest version from GitHub
$transient_key = 'wp_seo_automater_github_release';
$github_data = get_transient( $transient_key );

$latest_version = 'Checking...';
$update_available = false;

if ( $github_data && isset( $github_data->tag_name ) ) {
    $latest_version = ltrim( $github_data->tag_name, 'v' );
    $update_available = version_compare( $current_version, $latest_version, '<' );
}

// System checks
$checks = array(
    'PHP Version' => array(
        'value' => PHP_VERSION,
        'required' => '7.4',
        'status' => version_compare( PHP_VERSION, '7.4', '>=' ) ? 'success' : 'error'
    ),
    'WordPress Version' => array(
        'value' => get_bloginfo( 'version' ),
        'required' => '5.8',
        'status' => version_compare( get_bloginfo( 'version' ), '5.8', '>=' ) ? 'success' : 'error'
    ),
    'Gemini API Key' => array(
        'value' => get_option( 'wp_seo_automater_gemini_key' ) ? 'Configured' : 'Not Set',
        'required' => 'Required',
        'status' => get_option( 'wp_seo_automater_gemini_key' ) ? 'success' : 'warning'
    ),
    'Unsplash API Key' => array(
        'value' => get_option( 'wp_seo_automater_unsplash_key' ) ? 'Configured' : 'Not Set',
        'required' => 'Optional',
        'status' => get_option( 'wp_seo_automater_unsplash_key' ) ? 'success' : 'info'
    ),
);
?>
<div class="wp-seo-wrap">
    <div class="wp-seo-header">
        <div>
            <h1><?php esc_html_e( 'System Info', 'wp-seo-blog-automater' ); ?></h1>
            <p class="wp-seo-subtitle"><?php esc_html_e( 'Plugin version, updates, and system status', 'wp-seo-blog-automater' ); ?></p>
        </div>
        <?php if ( file_exists( WP_SEO_AUTOMATER_PATH . 'images/logo.png' ) ) : ?>
            <div class="wp-seo-branding">
                <img src="<?php echo esc_url( WP_SEO_AUTOMATER_URL . 'images/logo.png' ); ?>" alt="<?php esc_attr_e( 'Codezela Technologies', 'wp-seo-blog-automater' ); ?>" class="wp-seo-logo">
            </div>
        <?php endif; ?>
    </div>

    <!-- Version Info -->
    <div class="wp-seo-card" id="version-info-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h2 style="margin: 0;"><?php esc_html_e( 'Version Information', 'wp-seo-blog-automater' ); ?></h2>
            <button type="button" id="check-updates-now" class="wp-seo-btn wp-seo-btn-secondary">
                <span class="dashicons dashicons-update" style="margin-right: 0.5rem;"></span>
                <?php esc_html_e( 'Check for Updates Now', 'wp-seo-blog-automater' ); ?>
            </button>
        </div>
        
        <div id="update-check-message"></div>
        
        <div class="wp-seo-info-grid" id="version-display">
            <div class="wp-seo-info-item">
                <span class="wp-seo-info-label"><?php esc_html_e( 'Current Version', 'wp-seo-blog-automater' ); ?></span>
                <span class="wp-seo-info-value">
                    <strong id="current-version-text"><?php echo esc_html( $current_version ); ?></strong>
                </span>
            </div>
            
            <div class="wp-seo-info-item">
                <span class="wp-seo-info-label"><?php esc_html_e( 'Latest Version', 'wp-seo-blog-automater' ); ?></span>
                <span class="wp-seo-info-value">
                    <strong id="latest-version-text"><?php echo esc_html( $latest_version ); ?></strong>
                </span>
            </div>
        </div>
        
        <div id="update-status-notice">
            <?php if ( $update_available ) : ?>
                <div class="wp-seo-notice wp-seo-notice-warning">
                    <p>
                        <strong><?php esc_html_e( 'Update Available!', 'wp-seo-blog-automater' ); ?></strong>
                        <?php 
                        printf(
                            esc_html__( 'Version %s is available. Go to %s to update.', 'wp-seo-blog-automater' ),
                            esc_html( $latest_version ),
                            '<a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">' . esc_html__( 'Plugins page', 'wp-seo-blog-automater' ) . '</a>'
                        );
                        ?>
                    </p>
                </div>
            <?php else : ?>
                <div class="wp-seo-notice wp-seo-notice-success">
                    <p>
                        <strong><?php esc_html_e( 'Up to Date', 'wp-seo-blog-automater' ); ?></strong>
                        <?php esc_html_e( 'You are running the latest version.', 'wp-seo-blog-automater' ); ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="wp-seo-actions" style="margin-top: 1.5rem;">
            <a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>" class="wp-seo-btn wp-seo-btn-primary">
                <?php esc_html_e( 'Go to Plugins Page', 'wp-seo-blog-automater' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'update-core.php' ) ); ?>" class="wp-seo-btn wp-seo-btn-secondary">
                <?php esc_html_e( 'WordPress Updates', 'wp-seo-blog-automater' ); ?>
            </a>
            <a href="https://github.com/sayuru-akash/wp-seo-blog-automater-plugin/releases" target="_blank" rel="noopener" class="wp-seo-btn wp-seo-btn-secondary">
                <?php esc_html_e( 'View Releases on GitHub', 'wp-seo-blog-automater' ); ?>
            </a>
        </div>
    </div>

    <!-- System Requirements -->
    <div class="wp-seo-card">
        <h2><?php esc_html_e( 'System Status', 'wp-seo-blog-automater' ); ?></h2>
        
        <table class="wp-seo-table widefat">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Component', 'wp-seo-blog-automater' ); ?></th>
                    <th><?php esc_html_e( 'Current', 'wp-seo-blog-automater' ); ?></th>
                    <th><?php esc_html_e( 'Required', 'wp-seo-blog-automater' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'wp-seo-blog-automater' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $checks as $label => $check ) : ?>
                    <tr>
                        <td><strong><?php echo esc_html( $label ); ?></strong></td>
                        <td><?php echo esc_html( $check['value'] ); ?></td>
                        <td><?php echo esc_html( $check['required'] ); ?></td>
                        <td>
                            <span class="wp-seo-badge <?php echo esc_attr( $check['status'] ); ?>">
                                <?php echo esc_html( ucfirst( $check['status'] === 'success' ? 'OK' : ( $check['status'] === 'warning' ? 'Warning' : 'Error' ) ) ); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- How Updates Work -->
    <div class="wp-seo-card">
        <h2><?php esc_html_e( 'How Automatic Updates Work', 'wp-seo-blog-automater' ); ?></h2>
        
        <div class="wp-seo-info-box">
            <h3><?php esc_html_e( 'Where to Find Updates', 'wp-seo-blog-automater' ); ?></h3>
            <ul style="list-style: disc; padding-left: 2rem; margin: 1rem 0;">
                <li><strong><?php esc_html_e( 'Dashboard → Updates', 'wp-seo-blog-automater' ); ?></strong> - <?php esc_html_e( 'Main WordPress updates page', 'wp-seo-blog-automater' ); ?></li>
                <li><strong><?php esc_html_e( 'Plugins → Installed Plugins', 'wp-seo-blog-automater' ); ?></strong> - <?php esc_html_e( 'Shows "update available" badge', 'wp-seo-blog-automater' ); ?></li>
                <li><strong><?php esc_html_e( 'Admin Toolbar', 'wp-seo-blog-automater' ); ?></strong> - <?php esc_html_e( 'Update count bubble at top', 'wp-seo-blog-automater' ); ?></li>
            </ul>
            
            <h3><?php esc_html_e( 'Update Schedule', 'wp-seo-blog-automater' ); ?></h3>
            <ul style="list-style: disc; padding-left: 2rem; margin: 1rem 0;">
                <li><?php esc_html_e( 'Checks GitHub every 12 hours automatically', 'wp-seo-blog-automater' ); ?></li>
                <li><?php esc_html_e( 'Manual check: Dashboard → Updates → "Check Again"', 'wp-seo-blog-automater' ); ?></li>
                <li><?php esc_html_e( 'One-click install from WordPress admin', 'wp-seo-blog-automater' ); ?></li>
                <li><?php esc_html_e( 'Settings and data are preserved during updates', 'wp-seo-blog-automater' ); ?></li>
            </ul>
        </div>
    </div>

    <!-- Plugin Info -->
    <div class="wp-seo-card">
        <h2><?php esc_html_e( 'Plugin Details', 'wp-seo-blog-automater' ); ?></h2>
        
        <div class="wp-seo-info-grid">
            <div class="wp-seo-info-item">
                <span class="wp-seo-info-label"><?php esc_html_e( 'Plugin Name', 'wp-seo-blog-automater' ); ?></span>
                <span class="wp-seo-info-value"><?php echo esc_html( $plugin_data['Name'] ); ?></span>
            </div>
            
            <div class="wp-seo-info-item">
                <span class="wp-seo-info-label"><?php esc_html_e( 'Author', 'wp-seo-blog-automater' ); ?></span>
                <span class="wp-seo-info-value">
                    <a href="<?php echo esc_url( $plugin_data['AuthorURI'] ); ?>" target="_blank" rel="noopener">
                        <?php echo esc_html( $plugin_data['Author'] ); ?>
                    </a>
                </span>
            </div>
            
            <div class="wp-seo-info-item">
                <span class="wp-seo-info-label"><?php esc_html_e( 'Plugin URI', 'wp-seo-blog-automater' ); ?></span>
                <span class="wp-seo-info-value">
                    <a href="<?php echo esc_url( $plugin_data['PluginURI'] ); ?>" target="_blank" rel="noopener">
                        <?php echo esc_html( $plugin_data['PluginURI'] ); ?>
                    </a>
                </span>
            </div>
            
            <div class="wp-seo-info-item">
                <span class="wp-seo-info-label"><?php esc_html_e( 'Text Domain', 'wp-seo-blog-automater' ); ?></span>
                <span class="wp-seo-info-value"><?php echo esc_html( $plugin_data['TextDomain'] ); ?></span>
            </div>
        </div>
    </div>

    <div class="wp-seo-footer">
        <p>
            <?php esc_html_e( 'Powered by', 'wp-seo-blog-automater' ); ?>
            <a href="https://codezela.com" target="_blank" rel="noopener">
                <strong><?php esc_html_e( 'Codezela Technologies', 'wp-seo-blog-automater' ); ?></strong>
            </a>
        </p>
    </div>
</div>
