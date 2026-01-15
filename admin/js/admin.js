jQuery(document).ready(function ($) {
  // Generate Button Click
  $("#btn-generate").on("click", function (e) {
    e.preventDefault();

    var title = $("#article_title").val();
    var keywords = $("#article_keywords").val();

    if (!title || !keywords) {
      alert("Please enter both a title and keywords.");
      return;
    }

    // UI Updates
    $(this).prop("disabled", true);
    $(".wp-seo-loader").show();
    $("#generation-results").addClass("wp-seo-hidden");

    $.ajax({
      url: wpSeoAutomater.ajax_url,
      type: "POST",
      data: {
        action: "wp_seo_generate_post",
        nonce: wpSeoAutomater.nonce,
        title: title,
        keywords: keywords,
      },
      success: function (response) {
        $(".wp-seo-loader").hide();
        $("#btn-generate").prop("disabled", false);

        if (response.success) {
          $("#generation-results").removeClass("wp-seo-hidden");

          // Populate fields
          // If the AI returned a JSON with title/content split, use it.
          // But our handler returns raw text string usually.
          // We'll rely on the PHP side to parse it or just put it all in content for now.
          // For better UX, let's assume PHP tries to split it or we just dump it in content.

          // Simple heuristic: If response.data.content is set
          $("#result_content").val(response.data.content);

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
          if (response.data.schema) {
            $("#result_schema").val(response.data.schema);
          }

          // Populate Yoast Meta Fields
          if (response.data.meta_title) {
            $("#result_meta_title").val(response.data.meta_title);
          }
          if (response.data.meta_desc) {
            $("#result_meta_desc").val(response.data.meta_desc);
          }

          // Populate Image
          if (response.data.image_url) {
            $("#result_image_url").val(response.data.image_url);
            $("#result_image_preview")
              .attr("src", response.data.image_url)
              .show();
            $("#result_image_credit").text(response.data.image_credit);
          } else {
            $("#result_image_preview").hide();
            $("#result_image_credit").text("");
          }

          // Title Logic:
          // 1. Use extracted title from AI (H1) if present
          // 2. Fallback to Regex match (JS side)
          // 3. Fallback to user input
          if (response.data.title) {
            $("#result_title").val(response.data.title);
          } else {
            // Fallback Regex
            var content = response.data.content;
            var titleMatch = content.match(/^#\s+(.+)$/m);
            if (titleMatch && titleMatch[1]) {
              $("#result_title").val(titleMatch[1]);
            } else {
              $("#result_title").val(title); // User provided inputs
            }
          }
        } else {
          alert("Error: " + response.data);
        }
      },
      error: function () {
        $(".wp-seo-loader").hide();
        $("#btn-generate").prop("disabled", false);
        alert("System Error. Please try again.");
      },
    });
  });

  // Publish Button Click
  $("#btn-publish").on("click", function (e) {
    e.preventDefault();

    var finalTitle = $("#result_title").val();
    var finalSlug = $("#result_slug").val();
    var finalContent = $("#result_content").val();
    var finalSchema = $("#result_schema").val();

    // Get Yoast values
    var finalMetaTitle = $("#result_meta_title").val();
    var finalMetaDesc = $("#result_meta_desc").val();
    var finalImageUrl = $("#result_image_url").val(); // New

    if (!finalTitle || !finalContent) {
      alert("Cannot publish empty content.");
      return;
    }

    $(this).text("Publishing...").prop("disabled", true);

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
        image_url: finalImageUrl, // Send it
      },
      success: function (response) {
        $("#btn-publish").text("Publish to WordPress").prop("disabled", false);

        if (response.success) {
          // Show success message with link
          $("#publish-message").html(
            '<span style="color:green;">Published! </span> <a href="' +
              response.data.post_url +
              '" target="_blank">View Post</a>'
          );
        } else {
          alert("Publish Error: " + response.data);
        }
      },
      error: function () {
        $("#btn-publish").text("Publish to WordPress").prop("disabled", false);
        alert("Network Error");
      },
    });
  });

  // Discard Button
  $("#btn-discard").on("click", function () {
    if (confirm("Are you sure you want to discard this content?")) {
      $("#generation-results").addClass("wp-seo-hidden");
      $("#result_content").val("");
      $("#result_title").val("");
      $("#result_slug").val("");
      $("#result_meta_title").val("");
      $("#result_meta_desc").val("");
      $("#publish-message").html("");
    }
  });
});
