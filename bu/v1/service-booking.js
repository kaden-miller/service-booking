jQuery(document).ready(function ($) {
  function isBlackoutDate(date) {
    var dateString = $.datepicker.formatDate("yy-mm-dd", date);
    if (service_booking_ajax_object.blackout_dates) {
      return (
        service_booking_ajax_object.blackout_dates.indexOf(dateString) !== -1
      );
    }
    return false;
  }

  function isServiceDisabled(date, service) {
    console.log("Service:", service); // Log the service object

    if (service === undefined) {
      console.error("Service is undefined");
      return false;
    }

    // Get the day of the week as a string
    var dayOfWeek = [
      "Sunday",
      "Monday",
      "Tuesday",
      "Wednesday",
      "Thursday",
      "Friday",
      "Saturday",
    ][date.getDay()];

    // Check if the day is disabled for the service
    return service[dayOfWeek] && service[dayOfWeek].disabled === "1";
  }

  $("#service-booking-calendar").datepicker({
    dateFormat: "yy-mm-dd",
    minDate: 0, // Disable past dates
    beforeShowDay: function (date) {
      var selectedService = $("#service-selector").val();
      var service = service_booking_ajax_object.services[selectedService];

      var isDisabled = isServiceDisabled(date, service);
      var isBlackout = isBlackoutDate(date);
      console.log(
        "Date",
        date,
        "Disabled:",
        isDisabled,
        "Blackout:",
        isBlackout
      );

      return [!(isDisabled || isBlackout)]; // Return the result as an array
    },
    onSelect: function (dateText, inst) {
      // Update the value of the selected-date hidden input field
      var selectedDate = $(this).datepicker("getDate");
      var formattedDate = $.datepicker.formatDate("yy-mm-dd", selectedDate);
      $("#selected-date").val(formattedDate);

      var selectedService = $("#service-selector").val();
      fetchTimeSlots(formattedDate, selectedService);
    },
  });

  $("#service-booking-calendar").on("input", function () {
    var selectedDate = $(this).datepicker("getDate");
    var dateString = $.datepicker.formatDate("yy-mm-dd", selectedDate);
    var selectedService = $("#service-selector").val();
    fetchTimeSlots(dateString, selectedService);
  });

  $("#service-booking-calendar").on("click", ".booking-time-slot", function () {
    var time = $(this).data("time");
    var service = $(this).data("service");
    $("#selected-time-slot").val(time);
    $("#selected-service").val(service);
    $("#booking-form").show();
  });

  $("#booking-form").on("submit", function (e) {
    e.preventDefault();

    var submitButton = $(this).find('input[type="submit"]');
    submitButton.prop("disabled", true);

    var selectedDate = $("#selected-date").val();
    var selectedTimeSlot = $("#selected-time-slot").val();
    var selectedService = $("#selected-service").val(); // Add this line
    var customer_name = $("#name").val();
    var customer_phone = $("#phone").val();
    var customer_email = $("#email").val().trim();

    $.ajax({
      type: "POST",
      url: service_booking_ajax_object.ajax_url,
      data: {
        action: "service_booking_process",
        security: service_booking_ajax_object.nonce,
        selected_date: $("#selected-date").val(),
        selected_service: $("#selected-service").val(),
        selected_time_slot: $("#selected-time-slot").val(),
        customer_name: $("#name").val(),
        customer_phone: $("#phone").val(),
        customer_email: customer_email,
      },

      success: function (response) {
        if (response.success) {
          console.log("success!");
          alert(response.data.message);

          // Update the available time slots with the new booked slots data
          var selected_date = $("#service-booking-calendar").datepicker(
            "getDate"
          );
          if (selected_date) {
            var formattedDate = $.datepicker.formatDate(
              "yy-mm-dd",
              selected_date
            );
            var selectedService = $("#service-selector").val();
            fetchTimeSlots(formattedDate, selectedService);
          }
        } else {
          alert("Error submitting the booking");
        }
        $("#service-booking-calendar").datepicker("setDate", null);
        $("#selected-time-slot").val("");
        $(".time-slot").removeClass("selected");
        $("#name").val("");
        $("#phone").val("");
        $("#email").val("");
        $("#service-booking-time-slots").html("");
      },

      error: function (jqXHR, textStatus, errorThrown) {
        console.error("Error submitting the booking:", errorThrown);
        alert("Error submitting the booking");
      },
    });
  });
  $("#service-selector").on("change", function () {
    var selectedDate = $("#service-booking-calendar").datepicker("getDate");
    $("#selected-date").val($.datepicker.formatDate("yy-mm-dd", selectedDate));
    var dateString = $.datepicker.formatDate("yy-mm-dd", selectedDate);

    var selectedService = $(this).val();
    fetchTimeSlots(dateString, selectedService);

    // Refresh the calendar to update the disabled days
    $("#service-booking-calendar").datepicker("refresh");
  });

  function fetchTimeSlots(date, serviceId) {
    $.post(service_booking_ajax_object.ajax_url, {
      action: "fetch_time_slots",
      selected_date: date,
      selected_service: serviceId,
      security: service_booking_ajax_object.nonce,
    }).done(function (response) {
      var available_slots = response.data.available_time_slots;
      var time_slots_html = "";

      // Get the service data
      var service = service_booking_ajax_object.services[serviceId];

      // Helper function to convert 24-hour time format to 12-hour time format with AM/PM
      function convertTo12Hour(time) {
        var timeArray = time.split(":");
        var hour = parseInt(timeArray[0]);
        var minute = parseInt(timeArray[1]);

        var ampm = hour >= 12 ? "pm" : "am";
        hour = hour % 12;
        hour = hour ? hour : 12; // the hour '0' should be '12'
        return hour + ":" + (minute < 10 ? "0" + minute : minute) + ampm;
      }

      // Generate the HTML for available time slots
      for (var i = 0; i < available_slots.length; i++) {
        var startTime = convertTo12Hour(available_slots[i]);
        var endTime = convertTo12Hour(
          moment(available_slots[i], "HH:mm")
            .add(parseInt(service.duration), "minutes")
            .format("HH:mm")
        );

        time_slots_html +=
          '<button class="booking-time-slot" data-time="' +
          available_slots[i] +
          '" data-service="' +
          serviceId +
          '">' +
          startTime +
          " - " +
          endTime +
          "</button>";
      }

      $("#available-time-slots").html(time_slots_html);
    });
  }

  $("#available-time-slots").on("click", ".booking-time-slot", function () {
    var timeSlot = $(this).data("time");
    var service = $(this).data("service");
    $("#selected-time-slot").val(timeSlot);
    $("#selected-service").val(service);
    $(".booking-time-slot").removeClass("selected");
    $(this).addClass("selected");
    $("#booking-form").show();
  });
});

// Show Payment if there is a cost
jQuery(document).ready(function ($) {
  // Update the cost displayed and show the checkout form if there's a cost
  function updateServiceCost() {
    var selectedService = $("#service-selector option:selected");
    var cost = parseFloat(selectedService.attr("data-cost")); // Use .attr() instead of .data()

    if (cost > 0) {
      $("#service-cost").text("Cost: $" + cost.toFixed(2));
      $("#checkout-form").show();
    } else {
      $("#service-cost").text("");
      $("#checkout-form").hide();
    }
  }

  // Initialize the cost displayed and checkout form visibility
  updateServiceCost();

  // Update the cost displayed and checkout form visibility when the service is changed
  $("#service-selector").on("change", function () {
    updateServiceCost();
  });
});
