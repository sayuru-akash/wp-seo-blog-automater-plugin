<?php

class WP_SEO_Automater_Admin {

	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct() {
	}

	/**
	 * Run the loader tc execute all of the hooks with WordPress.
	 */
	public function run() {
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		
		add_action( 'wp_ajax_wp_seo_generate_post', array( $this, 'ajax_generate_post' ) );
		add_action( 'wp_ajax_wp_seo_publish_post', array( $this, 'ajax_publish_post' ) );
	}

	/**
	 * AJAX: Generate Content
	 */
	public function ajax_generate_post() {
		check_ajax_referer( 'wp_seo_automater_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied.' );
		}

		$title = sanitize_text_field( $_POST['title'] );
		$keywords = sanitize_text_field( $_POST['keywords'] );
		
		$handler = new Gemini_API_Handler();
		$master_prompt = get_option( 'wp_seo_automater_master_prompt', $this->get_default_master_prompt() );

		self::log_activity( 'Generation Start', "Processing article: '{$title}' with keywords '{$keywords}'...", 'info' );
		$content = $handler->generate_article( $title, $keywords, $master_prompt );

		if ( is_wp_error( $content ) ) {
			self::log_activity( 'Generation Failed', "Title: $title - Error: " . $content->get_error_message(), 'error' );
			wp_send_json_error( $content->get_error_message() );
		}

		self::log_activity( 'Generation Success', "Generated article for: $title", 'success' );

		// Return content. We assume raw text/markdown.
		// Use a simple markdown parser or just nl2br if needed, but WP post content handles HTML well.
		// If Gemini returns Markdown, we might want to convert headers # to <h1> etc. 
		// For now, let's send raw and handle minimal parsing on save or just trust Gemini's HTML capability if prompted (current prompt asks for H1, H2 etc).
		// The prompt logic implies it writes formatted text. We can convert Markdown to HTML if needed using a simple regex replace for basic headers/bolding if it comes back as MD.
		// Quick Markdown-to-HTML fix for headers and bolding:
		$html_content = $this->markdown_to_html( $content );
		
		// 1. Extract Slug
		$slug = '';
		// Nuclear Regex: Match "Slug" -> anything -> colon -> optional tags -> optional quotes -> capture value
		if ( preg_match( '/Slug.*?:\s*(?:<\/?[^>]+>)*\s*[`\'"]?([^`\'"<\n\r]+)/is', $html_content, $matches ) ) {
			$slug = trim( strip_tags( $matches[1] ) );
			self::log_activity( 'Debug Slug', "Found slug: $slug", 'info' );
		} else {
			self::log_activity( 'Debug Slug', "Regex failed to match slug in content start: " . substr($html_content, 0, 500), 'error' );
		}

		// 2. Extract Title from H1 (Handle attributes like class="x")
		$extracted_title = '';
		if ( preg_match( '/<h1.*?>(.*?)<\/h1>/is', $html_content, $matches ) ) {
			$extracted_title = strip_tags( $matches[1] );
		}

		// 3. Clean Content
		if ( $extracted_title ) {
			// Split by H1 (permissive), take the second part
			$parts = preg_split( '/<h1.*?>.*?<\/h1>/is', $html_content, 2 );
			if ( count( $parts ) > 1 ) {
				$html_content = trim( $parts[1] );
			}
		} else {
            // Fallback: If no H1 found, maybe look for "Phase 2" and strip before it
            $parts = preg_split( '/Phase 2:.*?Article/is', $html_content, 2 );
            if ( count( $parts ) > 1 ) {
				$html_content = trim( $parts[1] );
			}
		}

		wp_send_json_success( array(
			'content' => $html_content,
			'slug'    => $slug,
			'title'   => $extracted_title // Send extracted title back
		));
	}

	/**
	 * AJAX: Publish Post
	 */
	public function ajax_publish_post() {
		check_ajax_referer( 'wp_seo_automater_nonce', 'nonce' );

		if ( ! current_user_can( 'publish_posts' ) ) {
			wp_send_json_error( 'Permission denied.' );
		}

		$title = sanitize_text_field( $_POST['title'] );
		$slug  = sanitize_title( $_POST['slug'] );
		// Allow HTML in content
		$content = wp_kses_post( $_POST['content'] ); 
		
		self::log_activity( 'Publish Start', "Attempting to publish post: '{$title}' with slug '{$slug}'...", 'info' );

		$post_id = wp_insert_post( array(
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_author'  => get_current_user_id(),
			'post_type'    => 'post'
		));

		if ( is_wp_error( $post_id ) ) {
			self::log_activity( 'Publish Failed', "Title: $title - Error: " . $post_id->get_error_message(), 'error' );
			wp_send_json_error( $post_id->get_error_message() );
		}

		self::log_activity( 'Publish Success', "Published Post ID: $post_id", 'success' );

		wp_send_json_success( array(
			'post_id' => $post_id,
			'post_url' => get_permalink( $post_id )
		));
	}

	/**
	 * Simple Logger
	 */
	public static function log_activity( $topic, $details, $status ) {
		$logs = get_option( 'wp_seo_automater_logs', array() );
		// Prepend new log
		array_unshift( $logs, array(
			'date'    => current_time( 'mysql' ),
			'topic'   => $topic,
			'details' => $details,
			'status'  => $status
		));
		// Keep last 200
		$logs = array_slice( $logs, 0, 200 );
		update_option( 'wp_seo_automater_logs', $logs );
	}

	/**
	 * Helper: Simple Markdown to HTML
	 */
	private function markdown_to_html( $text ) {
		// Convert Headers
		$text = preg_replace( '/^# (.*?)$/m', '<h1>$1</h1>', $text );
		$text = preg_replace( '/^## (.*?)$/m', '<h2>$1</h2>', $text );
		$text = preg_replace( '/^### (.*?)$/m', '<h3>$1</h3>', $text );
		
		// Bold
		$text = preg_replace( '/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text );
		
		// Lists (Simple bullet points)
		// This is tricky without a full parser, but let's try basic * handling
		// ... Actually, relying on WP to handle newlines is often better or just asking Gemini for HTML directly.
		// The prompt asks for "H1 Title", "H2", "H3". Gemini usually outputs Markdown.
		// Let's stick to core basic headers and bolding.
		
		return $text;
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard.
	 */
	public function add_plugin_admin_menu() {
		add_menu_page(
			'BP Automater', 
			'BP Automater', 
			'manage_options', 
			'wp-seo-automater', 
			array( $this, 'display_generator_page' ), 
			'dashicons-superhero', 
			100
		);

		add_submenu_page(
			'wp-seo-automater',
			'Generator',
			'Generator',
			'manage_options',
			'wp-seo-automater',
			array( $this, 'display_generator_page' )
		);

		add_submenu_page(
			'wp-seo-automater',
			'Settings',
			'Settings',
			'manage_options',
			'wp-seo-automater-settings',
			array( $this, 'display_settings_page' )
		);

		add_submenu_page(
			'wp-seo-automater',
			'Logs',
			'Logs',
			'manage_options',
			'wp-seo-automater-logs',
			array( $this, 'display_logs_page' )
		);
	}

	/**
	 * Register the stylesheets for the admin area.
	 */
	public function enqueue_styles( $hook ) {
		// Only load on our plugin pages
		if ( strpos( $hook, 'wp-seo-automater' ) === false ) {
			return;
		}

		wp_enqueue_style( 
			'wp-seo-automater-admin', 
			WP_SEO_AUTOMATER_URL . 'admin/css/style.css', 
			array(), 
			WP_SEO_AUTOMATER_VERSION, 
			'all' 
		);
		
		// Enqueue Google Fonts (Inter)
		wp_enqueue_style( 'google-fonts-inter', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap', false );
	}

	/**
	 * Register the JavaScript for the admin area.
	 */
	public function enqueue_scripts( $hook ) {
		if ( strpos( $hook, 'wp-seo-automater' ) === false ) {
			return;
		}

		wp_enqueue_script( 
			'wp-seo-automater-admin-js', 
			WP_SEO_AUTOMATER_URL . 'admin/js/admin.js', 
			array( 'jquery' ), 
			WP_SEO_AUTOMATER_VERSION, 
			false 
		);

		wp_localize_script( 'wp-seo-automater-admin-js', 'wpSeoAutomater', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'wp_seo_automater_nonce' ),
		));
	}

	/**
	 * Render the Settings Page.
	 */
	public function display_settings_page() {
		// Reset Prompt
		if ( isset( $_POST['wp_seo_automater_reset_prompt'] ) && check_admin_referer( 'wp_seo_automater_settings_save' ) ) {
			delete_option( 'wp_seo_automater_master_prompt' );
			self::log_activity( 'Settings', 'Master Prompt reset to default.', 'info' );
			echo '<div class="notice notice-info is-dismissible"><p>Master Prompt reset to default.</p></div>';
		}
		// Save settings if posted
		elseif ( isset( $_POST['wp_seo_automater_save_settings'] ) && check_admin_referer( 'wp_seo_automater_settings_save' ) ) {
			update_option( 'wp_seo_automater_gemini_key', sanitize_text_field( $_POST['gemini_api_key'] ) );
			update_option( 'wp_seo_automater_gemini_model', sanitize_text_field( $_POST['gemini_model_id'] ) );
			// Allow some HTML in prompt (e.g. line breaks) but sanitize heavily
			update_option( 'wp_seo_automater_master_prompt', wp_kses_post( $_POST['master_prompt'] ) );
			
			self::log_activity( 'Settings', 'Plugin settings updated.', 'success' );
			echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully.</p></div>';
		}

		$api_key = get_option( 'wp_seo_automater_gemini_key', '' );
		$model_id = get_option( 'wp_seo_automater_gemini_model', 'gemini-pro-latest' );
		$master_prompt = get_option( 'wp_seo_automater_master_prompt', $this->get_default_master_prompt() );

		include_once WP_SEO_AUTOMATER_PATH . 'admin/partials/settings-display.php';
	}

	/**
	 * Render the Generator Page.
	 */
	public function display_generator_page() {
		include_once WP_SEO_AUTOMATER_PATH . 'admin/partials/generator-display.php';
	}

	/**
	 * Render the Logs Page.
	 */
	public function display_logs_page() {
		include_once WP_SEO_AUTOMATER_PATH . 'admin/partials/logs-display.php';
	}

	/**
	 * Default Master Prompt.
	 */
	private function get_default_master_prompt() {
		return "Role & Persona:
You are the Lead SEO Content Strategist & Senior Medical Copywriter for Lumiere Optique. Your goal is to dominate the #1 Google ranking position for targeted keywords in Scottsdale, AZ. You are writing for high-net-worth individuals who value both medical precision and luxury fashion.

Voice: Professional, sophisticated, knowledgeable, and reassuring.

Perspective: You write as the Lumiere Optique Brand (we/our/the team). You do not write as a specific doctor and never make first-person medical claims (\"I recommend\").

Core Directives (Non-Negotiable)
1. Website Grounding (Mandatory Start)

Action: You MUST visit and analyze https://lumiereoptique.com/ before writing.

Purpose: Absorb the exact tone, service details, and brand philosophy. Use only this site as your source of truth for services and brand identity.

2. Content Depth & \"No-Fluff\" Strategy (Target: 2,300+ Words)

The Goal: Create the most comprehensive resource on the internet.

The \"Anti-Fluff\" Rule: Do not repeat points to hit the word count. Instead, go deeper:

Technical Depth: Explain why a frame material fails (e.g., \"titanium micro-fractures\") or how a diagnostic tool works.

Featured Snippet Optimization: You MUST include distinct bulleted or numbered lists within the body content (e.g., \"5 Common DIY Mistakes,\" \"Symptoms of X,\" \"Benefits of Y\"). Google loves these for \"Position 0\" rankings.

Commercial Intent: If the keyword is commercial, prioritize conversion (booking) but justify the article length by explaining the process and experience in detail.

3. Medical Responsibility (YMYL)

Safety First: Do not diagnose, promise results, or guarantee cures.

Language: Use qualifiers like \"may help,\" \"often improves,\" or \"clinical studies suggest.\"

Urgency: Always include guidance on when to seek urgent medical care.

4. E-E-A-T & Local SEO Integration

Expertise: Showcase deep knowledge of luxury brands (Cartier, Chanel, Tom Ford, Lindberg) and specific medical services verified on the website.

Hyper-Local Context: Do not just say \"Scottsdale.\" Mention specific environmental factors relevant to eye health, such as:

Arizona's intense UV index/glare.

Dryness from air conditioning or desert heat.

Dust during monsoon season.

Lifestyle needs: Golfing, driving, or resort living.

Content Creation Checklist
Phase 1: Meta Data (Output First)

Meta Title: <60 chars. Front-load keyword. Compelling & Scottsdale-specific.

Meta Description: ~155-160 chars. Keyword + Value Prop + Phone Number in text.

Slug: lowercase-hyphenated-keyword.

Phase 2: The Article (Structure & Formatting)

H1 Title: Catchy, includes primary keyword. Only one H1.

Introduction: Hook the reader immediately. Define the problem and position Lumiere Optique as the solution.

Keyword Strategy:

Primary: Natural placement in H1, first paragraph, and at least one H2.

LSI/Contextual: Use terms like 'eye doctor Scottsdale', 'optometrist', 'bespoke eyewear', 'comprehensive eye exam'.

Brand Mentions: Naturally reference 'Lumiere Optique' and specific verified brands.

Body Content:

Hierarchy: Logical H2 and H3 headers.

Readability: Short, elegant paragraphs. Single line spacing only.

Bolding: Use bold text sparingly for key takeaways/lists only. Do not bold full sentences.

Internal Linking: Provide 3-6 internal link suggestions (anchor text only) to relevant service pages.

Mandatory FAQ Section: Place at the end of the body (H2: \"Frequently Asked Questions\"). Include 5 distinct Q&As.

Phase 3: The Close

Tone Check: Ensure the voice is professional yet warm.

Mandatory CTA: The final paragraph must exactly match this format:

[Transition text encouraging health/style priority...] Book Your Appointment: (480) 699-1885 | Visit Us in Scottsdale, AZ

Phase 4: Technical Deliverables

Output Purity: No conversational filler. Just the content.

Schema Markup: Immediately after the CTA, provide a valid JSON-LD FAQPage Schema script block.

CRITICAL: The Schema Question/Answer text must match the on-page FAQ text word-for-word. Do not rewrite it.

Output Management Safety Rule
If you reach your output limit before completing the article
Stop at a natural paragraph break and write
[PAUSING FOR CONTINUATION]
Wait for the user to say Continue.
Do not rush the ending.
Do not compress the FAQ.
Do not truncate the schema.

Commercial Intent Handling Clarity
For commercial keywords, you must include
What happens during the visit
Who the service is best for
What outcomes can realistically be expected
When to book
Do not mention pricing unless verified on the website.

Bold Usage Control
Bold text is for key takeaways only.
Do not bold full sentences repeatedly.
Do not bold for visual noise.";
	}
}
