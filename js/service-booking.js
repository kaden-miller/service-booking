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
    // console.log("Service:", service); // Log the service object

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
    minDate: 2, // Disable past dates
    beforeShowDay: function (date) {
      var selectedService = $("#service-selector").val();
      var service = service_booking_ajax_object.services[selectedService];

      var isDisabled = isServiceDisabled(date, service);
      var isBlackout = isBlackoutDate(date);
      // console.log(
      //   "Date",
      //   date,
      //   "Disabled:",
      //   isDisabled,
      //   "Blackout:",
      //   isBlackout
      // );

      return [!(isDisabled || isBlackout)]; // Return the result as an array
    },
    onSelect: function (dateText, inst) {
      // Update the value of the selected-date hidden input field
      var selectedDate = $(this).datepicker("getDate");
      var formattedDate = $.datepicker.formatDate("yy-mm-dd", selectedDate);
      $("#selected-date").val(formattedDate);
      var formattedDateDisplay = moment(dateText).format("dddd - MM-DD-YYYY"); // Format the date using moment.js
      $("#selected-date-display").text(formattedDateDisplay); // Update this line to use formattedDate

      var selectedService = $("#service-selector").val();
      fetchTimeSlots(formattedDate, selectedService);
    },
  });

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

  $("#service-selector").on("change", function () {
    var selectedDate = $("#service-booking-calendar").datepicker("getDate");
    $("#selected-date").val($.datepicker.formatDate("yy-mm-dd", selectedDate));
    var dateString = $.datepicker.formatDate("yy-mm-dd", selectedDate);
    var formattedDate = moment(selectedDate).format("dddd - MM-DD-YYYY"); // Format the date using moment.js
    $("#selected-date-display").text(formattedDate); // Add this line

    var selectedService = $(this).val();
    fetchTimeSlots(dateString, selectedService);

    // Refresh the calendar to update the disabled days
    $("#service-booking-calendar").datepicker("refresh");

    // Update the value of #selected-service
    $("#selected-service").val(selectedService);
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

  // Show Payment if there is a cost

  // Update the cost displayed and show the checkout form if there's a cost
  function updateServiceCost() {
    var selectedService = $("#service-selector option:selected");
    var cost = parseFloat(selectedService.attr("data-cost")); // Use .attr() instead of .data()
    var description = selectedService.data("description");
    var name = selectedService.data("name");
    var duration = selectedService.data("duration");
    $("#service-name").text(name);
    $("#service-duration").text("Class Length: " + duration + " Minutes");
    $("#service-description").text(description);

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

  // // Add an event listener for the change event on the #service-selector
  // $("#service-selector").on("change", function () {
  //   // Update the value of the #selected-service with the value of the currently selected option
  //   $("#selected-service").val($(this).val());

  //   // Update the displayed cost
  //   var cost = $(this).find("option:selected").data("cost");
  //   // $("#service-cost").text("Cost: $" + cost.toFixed(2));
  //   if (cost > 0) {
  //     $("#service-cost").text("Cost: $" + cost.toFixed(2));
  //     $("#checkout-form").show();
  //   } else {
  //     $("#service-cost").text("");
  //     $("#checkout-form").hide();
  //   }
  // });

  // Trigger the change event on the #service-selector to set the initial values
  $("#service-selector").trigger("change");

  // Add this function to process the booking and submit the AJAX request
  function submitBooking() {
    var selectedDate = $("#selected-date").val();
    var selectedTimeSlot = $("#selected-time-slot").val();
    var selectedService = $("#selected-service").val(); // Add this line
    var customer_name = $("#customer-name").val();
    var customer_phone = $("#customer-phone").val();
    var customer_email = $("#customer-email").val().trim();

    $.ajax({
      type: "POST",
      url: service_booking_ajax_object.ajax_url,
      data: {
        action: "service_booking_process",
        security: service_booking_ajax_object.nonce,
        selected_date: $("#selected-date").val(),
        selected_service: $("#selected-service").val(),
        selected_time_slot: $("#selected-time-slot").val(),
        customer_name: $("#customer-name").val(),
        customer_phone: $("#customer-phone").val(),
        customer_email: customer_email,
      },

      success: function (response) {
        if (response.success) {
          if (response.data.redirect_url) {
            window.location.href = response.data.redirect_url;
          } else {
            console.log(response);
          }

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
          if (response.data && response.data.redirect_url) {
            window.location.href = response.data.redirect_url;
          } else {

          }
        }
        $("#service-booking-calendar").datepicker("setDate", null);
        $("#selected-time-slot").val("");
        $(".time-slot").removeClass("selected");
        $("#customer-name").val("");
        $("#customer-phone").val("");
        $("#customer-email").val("");
        $("#service-booking-time-slots").html("");
      },

      error: function (jqXHR, textStatus, errorThrown) {
        console.error("Error submitting the booking:", errorThrown);
        alert("Error submitting the booking");
      },
    });
  }

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
  $("#booking-form").on("submit", async function (e) {
    e.preventDefault();

    var stripe_publishable_key = stripe_keys.publishable_key;
    var stripe_secret_key = stripe_keys.secret_key;

    var submitButton = $(this).find('input[type="submit"]');
    submitButton.prop("disabled", true);

    var selectedService = $("#selected-service").val();
    var service = service_booking_ajax_object.services[selectedService];

    var cost = service ? service.cost : undefined;
    var serviceName = service ? service.name : undefined; // Get the service name

    var selectedDate = $("#selected-date").val();
    var formattedDate = moment(selectedDate, "YYYY-MM-DD").format("MM-DD-YY");

    var selectedTimeSlot = $("#selected-time-slot").val();
    var formattedTimeSlot = convertTo12Hour(selectedTimeSlot);

    var serviceID = $("#selected-service").val();
    var customerName = $("#customer-name").val();
    var customerPhone = $("#customer-phone").val();
    var customerEmail = $("#customer-email").val().trim();

    if (cost && cost > 0) {
      // Initialize Stripe
      var stripe = Stripe(stripe_publishable_key); // Replace with your publishable key

      // Create a Checkout Session
      try {
        const response = await fetch(service_booking_ajax_object.ajax_url, {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: `action=create_stripe_checkout_session&security=${
            service_booking_ajax_object.nonce
          }&cost=${cost}&service_name=${encodeURIComponent(
            serviceName
          )}&selected_date=${encodeURIComponent(
            formattedDate
          )}&selected_time_slot=${encodeURIComponent(
            formattedTimeSlot
          )}&service_id=${encodeURIComponent(
            serviceID
          )}&customer_name=${encodeURIComponent(
            customerName
          )}&customer_phone=${encodeURIComponent(
            customerPhone
          )}&customer_email=${encodeURIComponent(
            customerEmail
          )}&service_name=${encodeURIComponent(serviceName)}`, // Add the selected_service to the request body
        });

        if (!response.ok) {
          console.error(
            "Error creating Stripe Checkout session: ",
            response.statusText
          );
          alert("Error processing payment");
        } else {
          const data = await response.json();

          if (data.success) {
            const sessionId = data.data.sessionId;

            // Redirect to Stripe Checkout
            const { error } = await stripe.redirectToCheckout({
              sessionId: sessionId,
            });

            if (error) {
              console.error("Error redirecting to Stripe Checkout:", error);
              alert("Error processing payment");
            }
          } else {
            console.error("Error creating Stripe Checkout session:", data);
            alert("Error processing payment");
          }
        }
      } catch (error) {
        console.error("Error creating Stripe Checkout session:", error);
        alert("Error processing payment");
      }
    } else {
      // No cost associated, submit the form data
      submitBooking(); // Call the submitBooking function to submit the form data
    }
  });

  function formatPhoneNumber(input) {
    const value = input.value.replace(/\D/g, "").slice(0, 10); // Remove all non-digit characters and limit the length to 10 digits
    let formattedValue = "";

    for (let i = 0; i < value.length; i++) {
      if (i === 3 || i === 6) {
        formattedValue += "-";
      }
      formattedValue += value[i];
    }

    input.value = formattedValue;
  }

  document
    .getElementById("customer-phone")
    .addEventListener("input", function (event) {
      formatPhoneNumber(event.target);
    });

  $("#continue-to-step-2").on("click", function () {
    if (
      $("#selected-date").val() &&
      $("#selected-service").val() &&
      $("#selected-time-slot").val()
    ) {
      // Update selected service, date, and timeslot values
      var selectedService = $("#service-selector option:selected").text();

      // Format the date
      var rawDate = $("#selected-date").val();
      var dateObject = new Date(rawDate);
      var formattedDate =
        dateObject.getMonth() +
        1 +
        "-" +
        dateObject.getDate() +
        "-" +
        dateObject.getFullYear();

      // Format the time
      var rawTime = $("#selected-time-slot").val();
      var timeObject = moment(rawTime, "HH:mm");
      var formattedTime = timeObject.format("h:mma");

      $("#selected-details-service").text("Service: " + selectedService);
      $("#selected-details-date").text("Date: " + formattedDate);
      $("#selected-details-timeslot").text("Time: " + formattedTime);

      $("#step-1").hide();
      $("#step-2").show();
      $("#service-selector").hide();
      $("html, body").animate(
        {
          scrollTop: $("#bookingWrapper").offset().top,
        },
        1000
      );
    } else {
      alert("Please select a service, date, and time slot before continuing.");
    }
  });
  $("#go-back-to-step-1").on("click", function () {
    $("#step-2").hide();
    $("#step-1").show();
    $("#service-selector").show();
    $("html, body").animate(
      {
        scrollTop: $("#bookingWrapper").offset().top,
      },
      1000
    );
  });

  var initialServiceId = parseInt(
    $("#service-selector").data("initial-service-id")
  );
  if (initialServiceId >= 0) {
    $("#service-selector").val(initialServiceId);
    updateServiceCost();
    $("#service-booking-calendar").datepicker("refresh");
  }
});
