/**
 * WP SEO Blog Automater - Admin JavaScript
 *
 * @package    WP_SEO_Blog_Automater
 * @author     Codezela Technologies
 * @since      1.0.0
 */

jQuery(document).ready(function ($) {
  "use strict";

  /**
   * Generate Button Click Handler
   */
  $("#btn-generate").on("click", function (e) {
    e.preventDefault();

    var title = $("#article_title").val().trim();
    var keywords = $("#article_keywords").val().trim();

    // Validation
    if (!title || !keywords) {
      alert("Please enter both a title and keywords.");
      return;
    }

    // UI Updates
    var $btn = $(this);
    var originalText = $btn.find(".btn-text").text();

    $btn.prop("disabled", true);
    $btn.find(".btn-text").text("Generating...");
    $(".wp-seo-loader").show();
    $("#generation-results").addClass("wp-seo-hidden");
    $("#publish-message").html("");

    $.ajax({
      url: wpSeoAutomater.ajax_url,
      type: "POST",
      data: {
        action: "wp_seo_generate_post",
        nonce: wpSeoAutomater.nonce,
        title: title,
        keywords: keywords,
      },
      timeout: 120000, // 2 minutes timeout
      success: function (response) {
        $(".wp-seo-loader").hide();
        $btn.prop("disabled", false);
        $btn.find(".btn-text").text(originalText);

        if (response.success) {
          $("#generation-results").removeClass("wp-seo-hidden");

          // Populate content
          $("#result_content").val(response.data.content || "");

          // Populate Slug
          if (response.data.slug) {
            $("#result_slug").val(response.data.slug);
          } else {
            // Fallback: simple slugify from title
            var slug = title
              .toLowerCase()
              .replace(/ /g, "-")
              .replace(/[^\w-]+/g, "");
            $("#result_slug").val(slug);
          }

          // Populate Schema (Hidden)
          $("#result_schema").val(response.data.schema || "");

          // Populate Meta Fields
          $("#result_meta_title").val(response.data.meta_title || "");
          $("#result_meta_desc").val(response.data.meta_desc || "");

          // Populate Image
          if (response.data.image_url) {
            $("#result_image_url").val(response.data.image_url);
            $("#result_image_preview")
              .attr("src", response.data.image_url)
              .show();
            $("#result_image_credit").text(response.data.image_credit || "");
          } else {
            $("#result_image_preview").hide();
            $("#result_image_credit").text("");

            // DIAGNOSTIC ALERT
            if (response.data.debug_info) {
              console.warn("Unsplash Debug Info:", response.data.debug_info);
              if (
                response.data.debug_info.unsplash_status !== "Success" &&
                response.data.debug_info.unsplash_status !== "Not Attempted"
              ) {
                console.error(
                  "Image fetch failed:",
                  response.data.debug_info.unsplash_status,
                );
              }
            }
          }

          // Title Logic
          if (response.data.title) {
            $("#result_title").val(response.data.title);
          } else {
            // Fallback Regex
            var content = response.data.content;
            var titleMatch = content.match(/^#\s+(.+)$/m);
            if (titleMatch && titleMatch[1]) {
              $("#result_title").val(titleMatch[1]);
            } else {
              $("#result_title").val(title);
            }
          }

          // Scroll to results
          $("html, body").animate(
            {
              scrollTop: $("#generation-results").offset().top - 100,
            },
            500,
          );
        } else {
          var errorMsg = response.data || "Unknown error occurred";
          alert("Error: " + errorMsg);
          console.error("Generation error:", response);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        $(".wp-seo-loader").hide();
        $btn.prop("disabled", false);
        $btn.find(".btn-text").text(originalText);

        var errorMsg = "System Error. Please try again.";
        if (textStatus === "timeout") {
          errorMsg =
            "Request timed out. The AI may need more time. Please try again.";
        }
        alert(errorMsg);
        console.error("AJAX error:", textStatus, errorThrown);
      },
    });
  });

  /**
   * Publish Button Click Handler
   */
  $("#btn-publish").on("click", function (e) {
    e.preventDefault();

    var finalTitle = $("#result_title").val().trim();
    var finalSlug = $("#result_slug").val().trim();
    var finalContent = $("#result_content").val().trim();
    var finalSchema = $("#result_schema").val();
    var finalMetaTitle = $("#result_meta_title").val();
    var finalMetaDesc = $("#result_meta_desc").val();
    var finalImageUrl = $("#result_image_url").val();

    // Validation
    if (!finalTitle || !finalContent) {
      alert("Cannot publish empty content. Title and content are required.");
      return;
    }

    var $btn = $(this);
    var originalHtml = $btn.html();

    $btn
      .html('<span class="dashicons dashicons-update"></span> Publishing...')
      .prop("disabled", true);

    $.ajax({
      url: wpSeoAutomater.ajax_url,
      type: "POST",
      data: {
        action: "wp_seo_publish_post",
        nonce: wpSeoAutomater.nonce,
        title: finalTitle,
        slug: finalSlug,
        content: finalContent,
        schema: finalSchema,
        meta_title: finalMetaTitle,
        meta_desc: finalMetaDesc,
        image_url: finalImageUrl,
      },
      timeout: 60000, // 1 minute timeout
      success: function (response) {
        $btn.html(originalHtml).prop("disabled", false);

        if (response.success) {
          // Show success message with link
          $("#publish-message").html(
            '<span style="color: var(--success-color); font-weight: 600;">✓ Published Successfully!</span> ' +
              '<a href="' +
              response.data.post_url +
              '" target="_blank" rel="noopener" style="color: var(--primary-color); text-decoration: underline;">View Post →</a>',
          );

          // Optional: Clear form after success
          // setTimeout(function() {
          //   $("#btn-discard").trigger("click");
          // }, 3000);
        } else {
          var errorMsg = response.data || "Unknown publish error";
          alert("Publish Error: " + errorMsg);
          console.error("Publish error:", response);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        $btn.html(originalHtml).prop("disabled", false);

        var errorMsg =
          "Network Error. Please check your connection and try again.";
        if (textStatus === "timeout") {
          errorMsg = "Publish timed out. Please try again.";
        }
        alert(errorMsg);
        console.error("AJAX error:", textStatus, errorThrown);
      },
    });
  });

  /**
   * Discard Button Click Handler
   */
  $("#btn-discard").on("click", function () {
    if (
      confirm(
        "Are you sure you want to discard this content? This action cannot be undone.",
      )
    ) {
      $("#generation-results").addClass("wp-seo-hidden");
      $("#result_content").val("");
      $("#result_title").val("");
      $("#result_slug").val("");
      $("#result_meta_title").val("");
      $("#result_meta_desc").val("");
      $("#result_image_url").val("");
      $("#result_image_preview").hide();
      $("#result_image_credit").text("");
      $("#result_schema").val("");
      $("#publish-message").html("");

      // Scroll back to top
      $("html, body").animate(
        {
          scrollTop: $(".wp-seo-wrap").offset().top - 50,
        },
        300,
      );
    }
  });

  /**
   * Check for Updates Now Button Handler
   */
  $("#check-updates-now").on("click", function (e) {
    e.preventDefault();

    var $btn = $(this);
    var originalHtml = $btn.html();

    // Show loading state
    $btn.prop("disabled", true);
    $btn.html(
      '<span class="dashicons dashicons-update spin"></span> ' + "Checking...",
    );

    // Hide existing notices
    $("#update-status-notice").hide();

    // Show checking message
    $("#update-check-message")
      .removeClass("wp-seo-notice-success wp-seo-notice-error")
      .addClass("wp-seo-notice wp-seo-notice-info")
      .html("<p>" + "Checking for updates from GitHub..." + "</p>")
      .show();

    $.ajax({
      url: wpSeoAutomater.ajax_url,
      type: "POST",
      data: {
        action: "check_updates_now",
        nonce: wpSeoAutomater.nonce,
      },
      success: function (response) {
        $btn.prop("disabled", false);
        $btn.html(originalHtml);

        if (response.success) {
          var data = response.data;

          // Update version display
          $("#current-version-text").text(data.current_version);
          $("#latest-version-text").text(data.latest_version);

          // Show success/update message
          if (data.update_available) {
            $("#update-check-message")
              .removeClass("wp-seo-notice-info wp-seo-notice-success")
              .addClass("wp-seo-notice-warning")
              .html(
                "<p><strong>Update Available!</strong> " +
                  data.message +
                  "</p>",
              );

            // Update the status notice
            $("#update-status-notice")
              .html(
                '<div class="wp-seo-notice wp-seo-notice-warning">' +
                  "<p><strong>Update Available!</strong> " +
                  "Version " +
                  data.latest_version +
                  ' is available. Go to <a href="' +
                  wpSeoAutomater.admin_url +
                  'plugins.php">Plugins page</a> to update.' +
                  "</p>" +
                  "</div>",
              )
              .show();
          } else {
            $("#update-check-message")
              .removeClass("wp-seo-notice-info wp-seo-notice-warning")
              .addClass("wp-seo-notice-success")
              .html("<p><strong>Up to Date!</strong> " + data.message + "</p>");

            // Update the status notice
            $("#update-status-notice")
              .html(
                '<div class="wp-seo-notice wp-seo-notice-success">' +
                  "<p><strong>Up to Date</strong> " +
                  "You are running the latest version." +
                  "</p>" +
                  "</div>",
              )
              .show();
          }

          // Hide the check message after 5 seconds if no update
          if (!data.update_available) {
            setTimeout(function () {
              $("#update-check-message").fadeOut();
            }, 5000);
          }
        } else {
          // Error
          $("#update-check-message")
            .removeClass("wp-seo-notice-info wp-seo-notice-success")
            .addClass("wp-seo-notice-error")
            .html(
              "<p><strong>Error:</strong> " +
                (response.data.message || "Failed to check for updates.") +
                "</p>",
            );
        }
      },
      error: function () {
        $btn.prop("disabled", false);
        $btn.html(originalHtml);

        $("#update-check-message")
          .removeClass("wp-seo-notice-info wp-seo-notice-success")
          .addClass("wp-seo-notice-error")
          .html(
            "<p><strong>Error:</strong> " +
              "Network error. Please try again." +
              "</p>",
          );
      },
    });
  });
});
