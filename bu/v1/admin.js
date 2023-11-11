jQuery(document).ready(function ($) {
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
            $("#original-bookings-container").hide(); // Hide original bookings
            $("#filtered-bookings-container").html(response.data).show(); // Show filtered bookings
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
        $("#filtered-bookings-container").hide(); // Hide filtered bookings
      }
    },
  });
});
