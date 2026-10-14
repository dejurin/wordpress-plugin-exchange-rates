jQuery(document).ready(function ($) {
  $("#exchange-rates-force-data-update-button").click(function () {
    $(this).prop("disabled", true);
    $.ajax({
      type: "POST",
      url: ExchangeRatesForceDataUpdateAjax.ajaxurl,
      data: {
        action: "exchange_rates_force_data_update",
        nonce: ExchangeRatesForceDataUpdateAjax.nonce,
      },
      success: function (response) {
        if (response.success) {
          location.reload();
        }
        $("#exchange-rates-force-data-update-message").text(response.data);
      },
      error: function (response) {
        console.error(response);
        $("#exchange-rates-force-data-update-message").text(response.data);
      },
      complete: function () {
        $("#exchange-rates-force-data-update-button").prop("disabled", false);
      },
    });
  });
});
