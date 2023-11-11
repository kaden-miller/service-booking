jQuery(document).ready(function ($) {
  // Add event listener for the "Show All" button
  $("#show-all-bookings").on("click", function () {
    // Clear the date input
    $("#booking-filter-date").val("");

    // Show the original table with all bookings
    $("#original-bookings-container").show();
    // Remove the filtered bookings table if it exists
    $("#filtered-bookings-container").remove();
  });

  // Add event listener for the "Show Past Bookings" button
  $("#show-past-bookings").on("click", function () {
    $.ajax({
      url: service_booking_admin_object.ajax_url,
      type: "POST",
      data: {
        action: "fetch_past_bookings",
        security: service_booking_admin_object.nonce,
      },
      success: function (response) {
        if (response.success) {
          // Hide original bookings
          $("#original-bookings-container").hide();

          // Remove existing filtered bookings container if it exists
          $("#filtered-bookings-container").remove();

          // Create a new div for the past bookings container
          $("<div>")
            .attr("id", "filtered-bookings-container")
            .html(response.data)
            .insertAfter("#original-bookings-container");
        } else {
          console.error("Error fetching past bookings:", response);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error("AJAX error:", textStatus, errorThrown);
      },
    });
  });

  $("#booking-filter-date").datepicker({
    dateFormat: "mm-dd-yy",
    onSelect: function (dateText, inst) {
      var selectedDate = $("#booking-filter-date").datepicker("getDate");

      var formattedDate = $.datepicker.formatDate("yy-mm-dd", selectedDate);

      $.ajax({
        url: service_booking_admin_object.ajax_url,
        type: "POST",
        data: {
          action: "fetch_filtered_bookings",
          security: service_booking_admin_object.nonce,
          selected_date: formattedDate,
        },
        success: function (response) {
          if (response.success) {
            // Hide original bookings
            $("#original-bookings-container").hide();

            // Remove existing filtered bookings container if it exists
            $("#filtered-bookings-container").remove();

            // Create a new div for the filtered bookings container
            $("<div>")
              .attr("id", "filtered-bookings-container")
              .html(response.data)
              .insertAfter("#original-bookings-container");
          } else {
            console.error("Error fetching filtered bookings:", response);
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.error("AJAX error:", textStatus, errorThrown);
        },
      });
    },
    onClose: function (dateText, inst) {
      if (!dateText) {
        $("#original-bookings-container").show(); // Show original bookings
        $("#filtered-bookings-container").remove(); // Remove filtered bookings container if it exists
      }
    },
  });

  // Use jQuery's on method for event delegation
  $(".bookingTables").on("click", ".cancel-booking-btn", function () {
    var bookingId = $(this).data("booking-id");

    $.ajax({
      url: service_booking_admin_object.ajax_url,
      type: "POST",
      data: {
        action: "cancel_booking",
        security: service_booking_admin_object.nonce,
        booking_id: bookingId,
      },
      success: function (response) {
        if (response.success) {
          // Remove the cancelled booking row from the table
          $("button[data-booking-id='" + bookingId + "']")
            .closest("tr")
            .remove();
        } else {
          console.error("Error cancelling booking:", response);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error("AJAX error:", textStatus, errorThrown);
      },
    });
  });

  $("#add-booking-form").on("submit", function (event) {
    console.log("Submit event captured"); // add this line

    event.preventDefault();

    // Get form data
    var formData = $(this).serialize();

    // AJAX request to add the booking
    $.ajax({
      url: service_booking_admin_object.ajax_url,
      type: "POST",
      data: {
        action: "add_booking",
        security: $("#security").val(),
        customer_name: $('[name="customer_name"]').val(),
        customer_email: $('[name="customer_email"]').val(),
        customer_phone: $('[name="customer_phone"]').val(),
        service_id: $('[name="service_id"]').val(),
        date: $('[name="date"]').val(),
        time_slot: $('[name="time_slot"]').val(),
        recurring: $('[name="recurring"]').is(":checked"),
      },

      success: function (response) {
        if (response.success) {
          alert("Booking added successfully");
        } else {
          console.error("Error adding booking:", response);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error("AJAX error:", textStatus, errorThrown);
      },
    });
  });

  $(document).on("click", ".cancel-recurring-booking-btn", function (e) {
    e.preventDefault();

    var recurringId = $(this).data("recurring-id");
    var $thisRow = $(this).closest("tr"); // Get the closest table row to the button clicked

    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        action: "cancel_recurring",
        security: your_nonce,
        recurring_id: recurringId,
      },
      success: function (response) {
        if (response.success) {
          // Do something on success...
          // For example, remove the table row from the UI
          $thisRow.remove();
        } else {
          // Do something on error...
        }
      },
    });
  });
});
