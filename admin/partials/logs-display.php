<?php
/**
 * Logs Page Display
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
            <h1><?php echo esc_html_x( 'Activity Logs', 'Logs page title', 'wp-seo-blog-automater' ); ?></h1>
            <p class="wp-seo-subtitle"><?php esc_html_e( 'Monitor all content generation activity', 'wp-seo-blog-automater' ); ?></p>
        </div>
        <?php if ( file_exists( WP_SEO_AUTOMATER_PATH . 'images/logo.png' ) ) : ?>
            <div class="wp-seo-branding">
                <img src="<?php echo esc_url( WP_SEO_AUTOMATER_URL . 'images/logo.png' ); ?>" alt="<?php esc_attr_e( 'Codezela Technologies', 'wp-seo-blog-automater' ); ?>" class="wp-seo-logo">
            </div>
        <?php endif; ?>
    </div>

    <div class="wp-seo-card">
        <div class="wp-seo-table-wrapper">
            <table class="wp-seo-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Date', 'wp-seo-blog-automater' ); ?></th>
                        <th><?php esc_html_e( 'Topic', 'wp-seo-blog-automater' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'wp-seo-blog-automater' ); ?></th>
                        <th><?php esc_html_e( 'Details', 'wp-seo-blog-automater' ); ?></th>
                    </tr>
                </thead>
                <tbody id="logs-body">
                    <?php
                    $logs = get_option( 'wp_seo_automater_logs', array() );
                    if ( empty( $logs ) ) {
                        echo '<tr><td colspan="4">' . esc_html__( 'No logs found yet. Activity will appear here after generating content.', 'wp-seo-blog-automater' ) . '</td></tr>';
                    } else {
                        foreach ( $logs as $log ) {
                            $badge_class = 'default';
                            switch ( $log['status'] ) {
                                case 'success':
                                    $badge_class = 'success';
                                    break;
                                case 'error':
                                    $badge_class = 'error';
                                    break;
                                case 'warning':
                                    $badge_class = 'warning';
                                    break;
                                case 'info':
                                    $badge_class = 'info';
                                    break;
                            }
                            echo '<tr>';
                            echo '<td>' . esc_html( $log['date'] ) . '</td>';
                            echo '<td><strong>' . esc_html( $log['topic'] ) . '</strong></td>';
                            echo '<td><span class="wp-seo-badge ' . esc_attr( $badge_class ) . '">' . esc_html( ucfirst( $log['status'] ) ) . '</span></td>';
                            echo '<td class="wp-seo-log-details">' . esc_html( $log['details'] ) . '</td>';
                            echo '</tr>';
                        }
                    }
                    ?>
                </tbody>
            </table>
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
