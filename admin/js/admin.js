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

          // Optional: Try to regex extract H1 for title input if not present
          var content = response.data.content;
          var titleMatch = content.match(/^#\s+(.+)$/m);
          if (titleMatch && titleMatch[1]) {
            $("#result_title").val(titleMatch[1]);
          } else {
            $("#result_title").val(title); // User provided title
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
      $("#publish-message").html("");
    }
  });
});
