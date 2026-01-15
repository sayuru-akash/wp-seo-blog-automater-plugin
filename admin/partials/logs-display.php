<div class="wp-seo-wrap">
    <div class="wp-seo-header">
        <h1>Activity Logs</h1>
    </div>

    <div class="wp-seo-card">
        <p>Recent generation history will appear here.</p>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Topic</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="logs-body">
                <?php
                $logs = get_option( 'wp_seo_automater_logs', array() );
                if ( empty( $logs ) ) {
                    echo '<tr><td colspan="4">No logs found yet.</td></tr>';
                } else {
                    foreach ( $logs as $log ) {
                        $badge_class = ( $log['status'] === 'success' ) ? 'success' : 'error';
                        echo '<tr>';
                        echo '<td>' . esc_html( $log['date'] ) . '</td>';
                        echo '<td>' . esc_html( $log['topic'] ) . '</td>';
                        echo '<td><span class="wp-seo-badge ' . esc_attr( $badge_class ) . '">' . esc_html( ucfirst( $log['status'] ) ) . '</span></td>';
                        echo '<td>' . esc_html( short_content( $log['details'], 50 ) ) . '</td>'; // Helper needed or just full text
                        echo '</tr>';
                    }
                }
                
                // Quick helper for length if needed, or just remove function call
                function short_content($str, $len) {
                    return strlen($str) > $len ? substr($str, 0, $len) . "..." : $str;
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
