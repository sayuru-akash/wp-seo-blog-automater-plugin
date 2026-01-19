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
        <?php
        // Pagination settings
        $logs_per_page = 20;
        $current_page  = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $logs          = get_option( 'wp_seo_automater_logs', array() );
        $total_logs    = count( $logs );
        $total_pages   = ceil( $total_logs / $logs_per_page );
        $offset        = ( $current_page - 1 ) * $logs_per_page;
        
        // Reverse to show newest first
        $logs = array_reverse( $logs );
        
        // Get current page logs
        $current_logs = array_slice( $logs, $offset, $logs_per_page );
        ?>
        
        <?php if ( $total_logs > 0 ) : ?>
            <div class="wp-seo-logs-meta">
                <p><?php printf( esc_html__( 'Showing %d - %d of %d logs', 'wp-seo-blog-automater' ), $offset + 1, min( $offset + $logs_per_page, $total_logs ), $total_logs ); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="wp-seo-table-wrapper">
            <table class="wp-seo-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 160px;"><?php esc_html_e( 'Date', 'wp-seo-blog-automater' ); ?></th>
                        <th><?php esc_html_e( 'Topic', 'wp-seo-blog-automater' ); ?></th>
                        <th style="width: 100px;"><?php esc_html_e( 'Status', 'wp-seo-blog-automater' ); ?></th>
                        <th><?php esc_html_e( 'Details', 'wp-seo-blog-automater' ); ?></th>
                    </tr>
                </thead>
                <tbody id="logs-body">
                    <?php
                    if ( empty( $current_logs ) ) {
                        echo '<tr><td colspan="4" style="text-align: center; padding: 40px;">' . esc_html__( 'No logs found yet. Activity will appear here after generating content.', 'wp-seo-blog-automater' ) . '</td></tr>';
                    } else {
                        foreach ( $current_logs as $log ) {
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
        
        <?php if ( $total_pages > 1 ) : ?>
            <div class="wp-seo-pagination">
                <?php
                $base_url = remove_query_arg( 'paged' );
                
                // Previous button
                if ( $current_page > 1 ) {
                    echo '<a href="' . esc_url( add_query_arg( 'paged', $current_page - 1, $base_url ) ) . '" class="wp-seo-btn wp-seo-btn-secondary">← ' . esc_html__( 'Previous', 'wp-seo-blog-automater' ) . '</a>';
                }
                
                // Page numbers
                echo '<span class="wp-seo-pagination-info">';
                printf( 
                    esc_html__( 'Page %d of %d', 'wp-seo-blog-automater' ), 
                    $current_page, 
                    $total_pages 
                );
                echo '</span>';
                
                // Next button
                if ( $current_page < $total_pages ) {
                    echo '<a href="' . esc_url( add_query_arg( 'paged', $current_page + 1, $base_url ) ) . '" class="wp-seo-btn wp-seo-btn-secondary">' . esc_html__( 'Next', 'wp-seo-blog-automater' ) . ' →</a>';
                }
                ?>
            </div>
        <?php endif; ?>
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
