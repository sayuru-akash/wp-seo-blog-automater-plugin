<div class="wp-seo-wrap">
    <div class="wp-seo-header">
        <h1>Settings</h1>
    </div>

    <form method="post" action="">
        <?php wp_nonce_field( 'wp_seo_automater_settings_save' ); ?>
        
        <div class="wp-seo-card">
            <h2>API Configuration</h2>
            <div class="wp-seo-form-group">
                <label class="wp-seo-label" for="gemini_api_key">Gemini API Key</label>
                <input type="password" id="gemini_api_key" name="gemini_api_key" class="wp-seo-input" value="<?php echo esc_attr( $api_key ); ?>" placeholder="Enter your Gemini API Key here...">
                <p class="description" style="margin-top: 0.5rem; color: var(--text-muted); font-size: 0.85rem;">
                    You need a valid API key from Google AI Studio.
                </p>
            </div>

            <div class="wp-seo-form-group">
                <label class="wp-seo-label" for="gemini_model_id">Gemini Model ID</label>
                <input type="text" id="gemini_model_id" name="gemini_model_id" class="wp-seo-input" value="<?php echo esc_attr( $model_id ); ?>" placeholder="gemini-pro">
                <p class="description" style="margin-top: 0.5rem; color: var(--text-muted); font-size: 0.85rem;">
                    Enter the model version (e.g., <code>gemini-pro</code>, <code>gemini-1.5-pro</code>, or <code>gemini-3-flash</code>).
                </p>
            </div>
        </div>

        <div class="wp-seo-card">
            <h2>Master Prompt Configuration</h2>
            <div class="wp-seo-form-group">
                <label class="wp-seo-label" for="master_prompt">Master System Prompt</label>
                <textarea id="master_prompt" name="master_prompt" class="wp-seo-textarea large"><?php echo esc_textarea( $master_prompt ); ?></textarea>
                <p class="description" style="margin-top: 0.5rem; color: var(--text-muted); font-size: 0.85rem;">
                    This prompt defines the persona, voice, and rules for the AI. Edit with caution.
                </p>
            </div>
        </div>

        <button type="submit" name="wp_seo_automater_save_settings" class="wp-seo-btn">
            Save Settings
        </button>

        <button type="submit" name="wp_seo_automater_reset_prompt" class="wp-seo-btn secondary" style="margin-left: 1rem;" onclick="return confirm('Are you sure? This will overwrite your current Master Prompt with the default one.');">
            Reset to Default Prompt
        </button>
    </form>
</div>
