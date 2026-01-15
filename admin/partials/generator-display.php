<div class="wp-seo-wrap">
    <div class="wp-seo-header">
        <h1>Article Generator</h1>
        <div id="connection-status" class="wp-seo-badge success wp-seo-hidden">System Ready</div>
    </div>

    <div class="wp-seo-card">
        <h2>New Article Details</h2>
        <div class="wp-seo-form-group">
             <label class="wp-seo-label" for="article_title">Article Concept / Working Title</label>
             <input type="text" id="article_title" class="wp-seo-input" placeholder="e.g. Benefits of Titanium Frames for Scottsdale Golfers">
        </div>

        <div class="wp-seo-form-group">
             <label class="wp-seo-label" for="article_keywords">Target Keywords (Comma separated)</label>
             <input type="text" id="article_keywords" class="wp-seo-input" placeholder="e.g. luxury eyewear scottsdale, titanium glasses, lightweight frames">
        </div>

        <div class="wp-seo-form-group">
            <button id="btn-generate" type="button" class="wp-seo-btn">
                <span class="wp-seo-loader"></span>
                Generate Article
            </button>
        </div>
    </div>

    <!-- Results Area (Hidden by default) -->
    <div id="generation-results" class="wp-seo-hidden">
        <div class="wp-seo-card">
            <h2>Generated Content Preview</h2>
            
            <div class="wp-seo-form-group">
                <label class="wp-seo-label">Title</label>
                <input type="text" id="result_title" class="wp-seo-input">
            </div>

            <div class="wp-seo-form-group">
                <label class="wp-seo-label">URL Slug</label>
                <input type="text" id="result_slug" class="wp-seo-input">
            </div>

            <div class="wp-seo-form-group">
                <label class="wp-seo-label">Content (HTML)</label>
                <textarea id="result_content" class="wp-seo-textarea large" style="min-height: 600px;"></textarea>
            </div>

            <div class="flex items-center">
                <button id="btn-publish" type="button" class="wp-seo-btn">
                    Publish to WordPress
                </button>
                <button id="btn-discard" type="button" class="wp-seo-btn secondary">
                    Discard
                </button>
                <span id="publish-message" style="margin-left: 1rem; font-weight: 500;"></span>
            </div>
        </div>
    </div>
</div>
