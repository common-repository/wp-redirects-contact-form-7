jQuery(document).ready(function ($) {
  // Handle view of tabs on radio button click

  // Show current URL Tab/dropdown Tab
  if (jQuery("input[name=type]:checked").val() == "2") {
    jQuery(".select_url").show();
    jQuery(".select_page").hide();
  } else {
    jQuery(".select_url").hide();
    jQuery(".select_page").show();
  }
  // Change current URL Tab/dropdown Tab
  jQuery("input[name=type]").change(function () {
    if (jQuery(this).val() == "2") {
      jQuery(".select_url").show();
      jQuery(".select_page").hide();
    } else {
      jQuery(".select_url").hide();
      jQuery(".select_page").show();
    }
  });

  // File Selection Code For CF7 Submission Download
  var file_frame;

  function resetPreview() {
    $("#file-preview").hide();
    $("#file-image").hide().attr("src", "");
    $("#file-details").hide();
    $("#file-icon img").attr("src", "");
    $("#file-title").text("");
    $("#file-name").text("");
    $("#file-size").text("");
    $("#select-file-button").show();
    $("#file-url").val("");
    $("#file-id").val("");
  }

  function displayFileData(fileData) {
    var isImage = /^image\//.test(fileData.type);
    if (isImage) {
      $("#file-image").attr("src", fileData.url).show();
      $("#file-details").hide();
    } else if (fileData.type === "image") {
      $("#file-image").attr("src", fileData.url).show();
      $("#file-details").hide();
    } else {
      $("#file-image").hide();
      $("#file-icon img").attr("src", fileData.icon);
      $("#file-title").text(fileData.title);
      $("#file-name").html(
        'File name: <a href="' +
          fileData.url +
          '" target="_blank">' +
          fileData.name +
          "</a>"
      );
      $("#file-size").text(
        "File size: " + (fileData.size ? fileData.size : "N/A")
      );
      $("#file-details").css("display", "flex");
      $("#file-details").show();
    }

    $("#file-preview").show();
    $("#select-file-button").hide();
    $("#file-url").val(fileData.url);
    $("#file-id").val(fileData.id);
  }

  $("#select-file-button, #change-file-button").on("click", function (event) {
    event.preventDefault();

    // If the media frame already exists, reopen it.
    if (file_frame) {
      file_frame.open();
      return;
    }

    // Create the media frame.
    file_frame = wp.media.frames.file_frame = wp.media({
      title: "Select a file",
      button: {
        text: "Use this file",
      },
      multiple: false,
    });

    // When a file is selected, run a callback.
    file_frame.on("select", function () {
      var attachment = file_frame.state().get("selection").first().toJSON();
      var fileData = {
        url: attachment.url,
        id: attachment.id,
        type: attachment.type,
        icon: attachment.icon,
        title: attachment.title,
        name: attachment.filename,
        size: attachment.filesizeHumanReadable
          ? attachment.filesizeHumanReadable
          : "N/A",
      };
      displayFileData(fileData);
    });

    // Finally, open the modal
    file_frame.open();
  });

  $("#remove-file-button").on("click", function (event) {
    event.preventDefault();
    resetPreview();
  });

  // Display existing file data if present
  var existingFileUrl = $("#selected-file-container").data("file-url");
  var existingFileId = $("#selected-file-container").data("file-id");
  var existingFileType = $("#selected-file-container").data("file-type");
  var existingFileIcon = $("#selected-file-container").data("file-icon");
  var existingFileTitle = $("#selected-file-container").data("file-title");
  var existingFileName = $("#selected-file-container").data("file-name");
  var existingFileSize = $("#selected-file-container").data("file-size");

  if (existingFileUrl && existingFileId) {
    var existingFileData = {
      url: existingFileUrl,
      id: existingFileId,
      type: existingFileType,
      icon: existingFileIcon,
      title: existingFileTitle,
      name: existingFileName,
      size: existingFileSize,
    };
    displayFileData(existingFileData);
  }

  //Custom JS Code Editor Code
  var cm_settings = wp.codeEditor.defaultSettings
    ? _.clone(wp.codeEditor.defaultSettings)
    : {};
  cm_settings.codemirror = _.extend({}, cm_settings.codemirror, {
    mode: "javascript",
    lineNumbers: true,
    matchBrackets: true,
    autoCloseBrackets: true,
    styleActiveLine: true,
  });
  wp.codeEditor.initialize($("#custom_js_cf7"), cm_settings);
});

// For Redirect Settings using Post TYpe Selection
document.addEventListener("DOMContentLoaded", function () {
  const selectType = document.getElementById("select_type");
  const selectOptions = document.getElementById("succ_page_id");

  const succPageId = document.getElementById("content_load_page_id").value;
  const selectedPostType = document.getElementById(
    "content_load_post_type"
  ).value;

  function updateOptions() {
    const selectedType = selectType.value;
    const xhr = new XMLHttpRequest();
    xhr.open("POST", passed_object.url);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onload = function () {
      if (xhr.status === 200) {
        selectOptions.innerHTML = xhr.responseText;

        if (succPageId) {
          const options = selectOptions.options;
          for (let i = 0; i < options.length; i++) {
            if (options[i].value == succPageId) {
              options[i].selected = true;
              break;
            }
          }
        }
      }
    };
    const params = `action=custom_get_options&type=${encodeURIComponent(
      selectedType
    )}&_wpnonce=${encodeURIComponent(passed_object.nonce)}`;
    xhr.send(params);
  }

  if (selectedPostType) {
    selectType.value = selectedPostType;
    updateOptions();
  }
  selectType.addEventListener("change", updateOptions);
});
