(function (window, $) {
  'use strict';

  var SFC = window.SFC;
  var stateApi = SFC.stateApi;

  SFC.quoteApi = {
    showError: function (message) {
      var $alert = $('#sfc-alert');
      if (!$alert.length) {
        return;
      }
      $alert.text(message).show();
    },

    hideError: function () {
      $('#sfc-alert').hide();
    },

    fetch: function () {
      if (!stateApi.isQuoteReady()) {
        SFC.quote = null;
        SFC.quoteApi.hideError();
        SFC.render.updateAfterQuote();
        return $.Deferred().resolve().promise();
      }

      SFC.root.classList.add('sfc--loading');
      SFC.quoteApi.hideError();

      var requestId = ++SFC.quoteRequestId;

      return $.post(SFC.data.ajaxUrl, {
        action: 'sfc_calculate_product_quote',
        product_slug: SFC.data.productSlug,
        state: JSON.stringify(SFC.state),
      })
        .done(function (res) {
          if (requestId !== SFC.quoteRequestId) {
            return;
          }
          if (res.success) {
            SFC.quote = res.data;
          } else {
            SFC.quote = null;
            SFC.quoteApi.showError(
              (res.data && res.data.message) ||
                SFC.strings.cart_error ||
                'No se pudo calcular la cotización.'
            );
          }
        })
        .fail(function () {
          if (requestId !== SFC.quoteRequestId) {
            return;
          }
          SFC.quote = null;
          SFC.quoteApi.showError(SFC.strings.cart_error || 'No se pudo calcular la cotización.');
        })
        .always(function () {
          if (requestId !== SFC.quoteRequestId) {
            return;
          }
          SFC.root.classList.remove('sfc--loading');
          SFC.render.updateAfterQuote();
        });
    },

    schedule: function () {
      clearTimeout(SFC.quoteTimer);
      SFC.quoteTimer = setTimeout(SFC.quoteApi.fetch, 150);
    },
  };
})(window, jQuery);
