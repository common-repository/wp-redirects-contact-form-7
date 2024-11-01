jQuery(document).ready(function () {
  var url = "";

  jQuery(".wpcf7-form").submit(function () {
    var id = jQuery(this).find("input[name=_wpcf7]").val();

    // Handle redirection after successfully sending mail
    document.addEventListener( "wpcf7mailsent", function (event) {
        // var id = jQuery("input[name=_wpcf7]").val(); // Contact form id

        // Ajax form submit
        jQuery.ajax({
          url: passed_object.url, //Passed object values from plugin main file
          method: "POST",
          data: {
            _ajax_nonce: passed_object.nonce, //Passed object values from plugin main file
            action: passed_object.action, //Passed object values from plugin main file
            id: id,
          },
          errors: function (errors) {
            return false;
          },
          success: function (response) {
            console.log(response);
            if (response.success) {
              submission_need = response.data.submission_need;
              if (
                response.data.file_url.length != 0 &&
                submission_need.length != 0
              ) {
                jQuery("body").append(
                  '<a id="cf7fd-attachment-link" href="' +
                    response.data.file_url +
                    '" target="_blank" download="' +
                    response.data.file_title +
                    '"></a>'
                );
                jQuery("#cf7fd-attachment-link")[0].click();
                setTimeout(function () {
                  jQuery("#cf7fd-attachment-link").remove();
                }, 2000);
              }

              url = response.data.url;
              redirection_needed = response.data.redirection_need;
              if (url.length != 0 && redirection_needed.length != 0) {
                location = url; // Redirect if url given to form
              }
            }
          },
        });

        handleFeedbackMessage(event.detail.apiResponse);
      },
      false
    );
    // Handle invalid fields
    document.addEventListener( "wpcf7invalid", function (event) {
        handleFeedbackMessage(event.detail.apiResponse);
      },
      false
    );

    // Handle spam detection
    document.addEventListener( "wpcf7spam", function (event) {
        handleFeedbackMessage(event.detail.apiResponse);
      },
      false
    );

    // Handle mail failure
    document.addEventListener( "wpcf7mailfailed", function (event) {
        handleFeedbackMessage(event.detail.apiResponse);
      },
      false
    );

    function handleFeedbackMessage(response) {

      var submission_popup_needed = response.yspl_cf7r_submission_popup_needed;
      var mail_sent_popup_needed = response.popup_mail_sent;
      var invalid_entry_popup_needed = response.popup_warning_invalid;
      var failed_mail_popup_needed = response.popup_failed_mail;
      var recaptcha_popup_needed = response.popup_failed_recaptcha;

      if (response.status === "mail_sent") {
        if ( submission_popup_needed.length != 0 && mail_sent_popup_needed.length != 0 ) {
          jQuery(".wpcf7-response-output").hide();
          Swal.fire({
            title: "<strong>" + "Sucesss" + "</strong>",
            icon: "success",
            html: response.message,
            showCloseButton: true,
            showConfirmButton: false,
            customClass: {
              closeButton: "custom-close-button", // Add your custom class here
            },
          });
        } else {
          jQuery(".wpcf7-response-output").show();
        }
      } else if (response.status === "validation_failed") {
        if ( submission_popup_needed.length != 0 && invalid_entry_popup_needed.length != 0 ) {
          jQuery(".wpcf7-response-output").hide();
          Swal.fire({
            title: "<strong>" + "Validation Error" + "</strong>",
            icon: "warning",
            html: response.message,
            showCloseButton: true,
            showConfirmButton: false,
            customClass: {
              closeButton: "custom-close-button", // Add your custom class here
            },
          });
        } else {
          jQuery(".wpcf7-response-output").show();
        }
      } else if (response.status === "spam") {
        if ( submission_popup_needed.length != 0 && recaptcha_popup_needed.length != 0 ) {
          jQuery(".wpcf7-response-output").hide();
          Swal.fire({
            title: "<strong>" + "Vaildate Recaptcha" + "</strong>",
            icon: "error",
            html: response.message,
            showCloseButton: true,
            showConfirmButton: false,
            customClass: {
              closeButton: "custom-close-button", // Add your custom class here
            },
          });
        } else {
          jQuery(".wpcf7-response-output").show();
        }
      } else if (response.status === "mail_failed") {
        if ( submission_popup_needed.length != 0 && failed_mail_popup_needed.length != 0 ) {
          jQuery(".wpcf7-response-output").hide();
          Swal.fire({
            title: "<strong>" + "Email Failure" + "</strong>",
            icon: "error",
            html: response.message,
            showCloseButton: true,
            showConfirmButton: false,
            customClass: {
              closeButton: "custom-close-button", // Add your custom class here
            },
          });
        } else {
          jQuery(".wpcf7-response-output").show();
        }
      } else {
        if (submission_popup_needed.length != 0) {
          jQuery(".wpcf7-response-output").hide();
          Swal.fire({
            title: "<strong>" + "Unexpected Error" + "</strong>",
            icon: "error",
            html: "An unknown error occurred. Please try again later.",
            showCloseButton: true,
            showConfirmButton: false,
            customClass: {
              closeButton: "custom-close-button", // Add your custom class here
            },
          });
        } else {
          jQuery(".wpcf7-response-output").show();
        }
      }
    }
  });
});
