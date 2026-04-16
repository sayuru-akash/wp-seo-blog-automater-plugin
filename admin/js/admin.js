/**
 * WP SEO Blog Automater - Admin JavaScript
 *
 * @package    WP_SEO_Blog_Automater
 * @author     Codezela Technologies
 * @since      1.0.0
 */

jQuery(document).ready(function ($) {
  "use strict";

  var generationTimeoutMs =
    parseInt(wpSeoAutomater.generation_timeout_ms, 10) || 300000;

  function extractAjaxErrorMessage(jqXHR, textStatus, fallbackMessage) {
    if (textStatus === "timeout") {
      return "Request timed out. The AI may need more time. Please try again.";
    }

    if (jqXHR && jqXHR.responseJSON) {
      if (typeof jqXHR.responseJSON.data === "string" && jqXHR.responseJSON.data) {
        return jqXHR.responseJSON.data;
      }

      if (
        jqXHR.responseJSON.data &&
        typeof jqXHR.responseJSON.data.message === "string" &&
        jqXHR.responseJSON.data.message
      ) {
        return jqXHR.responseJSON.data.message;
      }

      if (
        typeof jqXHR.responseJSON.message === "string" &&
        jqXHR.responseJSON.message
      ) {
        return jqXHR.responseJSON.message;
      }
    }

    if (jqXHR && typeof jqXHR.responseText === "string" && jqXHR.responseText) {
      var responseText = $("<div>").html(jqXHR.responseText).text().trim();
      responseText = responseText.replace(/\s+/g, " ");

      if (responseText) {
        return responseText.substring(0, 300);
      }
    }

    if (jqXHR && jqXHR.status) {
      return (
        (fallbackMessage || "System Error. Please try again.") +
        " (HTTP " +
        jqXHR.status +
        ")"
      );
    }

    return fallbackMessage || "System Error. Please try again.";
  }

  function getUsedImageIds() {
    var raw = $("#result_used_image_ids").val() || "[]";

    try {
      var parsed = JSON.parse(raw);
      return Array.isArray(parsed) ? parsed : [];
    } catch (e) {
      return [];
    }
  }

  function setUsedImageIds(ids) {
    $("#result_used_image_ids").val(JSON.stringify(ids));
  }

  function rememberImageId(imageId) {
    if (!imageId) {
      return;
    }

    var ids = getUsedImageIds();
    if (ids.indexOf(imageId) === -1) {
      ids.push(imageId);
      setUsedImageIds(ids);
    }
  }

  function updateImageState(data) {
    var debugInfo = data.debug_info || {};

    $("#result_image_keywords").val(debugInfo.keywords || "");
    $("#btn-refresh-image").prop("disabled", false);

    if (data.image_id) {
      $("#result_image_id").val(data.image_id);
      rememberImageId(data.image_id);
    }

    if (data.image_url) {
      $("#result_image_url").val(data.image_url);
      $("#result_image_preview").attr("src", data.image_url).show();
      $("#result_image_credit").text(data.image_credit || "");
      $("#image-refresh-message")
        .text(
          debugInfo.image_query_used
            ? "Image query: " + debugInfo.image_query_used
            : "",
        )
        .toggle(!!debugInfo.image_query_used);
    } else {
      $("#result_image_url").val("");
      $("#result_image_id").val("");
      $("#result_image_preview").hide();
      $("#result_image_credit").text("");
      $("#image-refresh-message")
        .text(debugInfo.unsplash_status || "No image found.")
        .show();
    }

    if (
      debugInfo &&
      debugInfo.unsplash_status &&
      debugInfo.unsplash_status !== "Success" &&
      debugInfo.unsplash_status !== "Not Attempted"
    ) {
      console.warn("Unsplash Debug Info:", debugInfo);
    }
  }

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
    $("#image-refresh-message").hide().text("");
    setUsedImageIds([]);
    $("#result_image_id").val("");
    $("#result_image_keywords").val("");
    $("#btn-refresh-image").prop("disabled", true);

    $.ajax({
      url: wpSeoAutomater.ajax_url,
      type: "POST",
      data: {
        action: "wp_seo_generate_post",
        nonce: wpSeoAutomater.nonce,
        title: title,
        keywords: keywords,
      },
      timeout: generationTimeoutMs,
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
          updateImageState(response.data);

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

        var errorMsg = extractAjaxErrorMessage(
          jqXHR,
          textStatus,
          "System Error. Please try again.",
        );
        alert(errorMsg);
        console.error("AJAX error:", textStatus, errorThrown);
      },
    });
  });

  /**
   * Refresh Image Button Click Handler
   */
  $("#btn-refresh-image").on("click", function (e) {
    e.preventDefault();

    var title = $("#result_title").val().trim();
    var metaTitle = $("#result_meta_title").val().trim();
    var content = $("#result_content").val().trim();
    var imageKeywords = $("#result_image_keywords").val().trim();

    if (!title || !content) {
      alert("Generate an article first before refreshing the image.");
      return;
    }

    if (!imageKeywords) {
      alert("Enter image search keywords before refreshing the image.");
      $("#result_image_keywords").trigger("focus");
      return;
    }

    var $btn = $(this);
    var originalText = $btn.find(".btn-text").text();

    $btn.prop("disabled", true);
    $btn.find(".btn-text").text("Refreshing...");
    $("#image-refresh-message").text("Finding a different image...").show();

    $.ajax({
      url: wpSeoAutomater.ajax_url,
      type: "POST",
      data: {
        action: "wp_seo_refresh_image",
        nonce: wpSeoAutomater.nonce,
        title: title,
        meta_title: metaTitle,
        content: content,
        image_keywords: imageKeywords,
        used_image_ids: JSON.stringify(getUsedImageIds()),
      },
      timeout: 60000,
      success: function (response) {
        $btn.prop("disabled", false);
        $btn.find(".btn-text").text(originalText);

        if (response.success) {
          updateImageState(response.data);
        } else {
          $("#image-refresh-message")
            .text(
              (response.data && response.data.message) ||
                "Could not refresh the image.",
            )
            .show();
        }
      },
      error: function (jqXHR, textStatus) {
        $btn.prop("disabled", false);
        $btn.find(".btn-text").text(originalText);
        $("#image-refresh-message")
          .text(
            extractAjaxErrorMessage(
              jqXHR,
              textStatus,
              "Image refresh failed. Please try again.",
            ),
          )
          .show();
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

        var errorMsg = extractAjaxErrorMessage(
          jqXHR,
          textStatus,
          "Network Error. Please check your connection and try again.",
        );
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
      $("#result_image_id").val("");
      $("#result_image_keywords").val("");
      setUsedImageIds([]);
      $("#result_image_preview").hide();
      $("#result_image_credit").text("");
      $("#image-refresh-message").hide().text("");
      $("#btn-refresh-image").prop("disabled", true);
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
        console.log("AJAX Response:", response);
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
      error: function (xhr, status, error) {
        console.error("AJAX Error:", {
          xhr: xhr,
          status: status,
          error: error,
          responseText: xhr.responseText,
        });
        $btn.prop("disabled", false);
        $btn.html(originalHtml);

        $("#update-check-message")
          .removeClass("wp-seo-notice-info wp-seo-notice-success")
          .addClass("wp-seo-notice-error")
          .html(
            "<p><strong>Error:</strong> " +
              "Network error. Please try again. Check browser console for details." +
              "</p>",
          );
      },
    });
  });
});
