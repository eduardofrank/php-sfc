(function (window, $) {
  'use strict';

  var SFC = window.SFC;

  SFC.events = {
    stepForField: function (field) {
      var steps = SFC.data.steps || [];
      var i;

      for (i = 0; i < steps.length; i++) {
        if (steps[i].field === field) {
          return steps[i];
        }
      }

      return null;
    },

    // Drop state values that the current selections no longer support: values
    // of steps that became invisible, and option values no longer present in a
    // step's (possibly dependent) options map. Loops until stable so cascades
    // like innerPaper → coverWeight → coverSurface settle in one pass.
    pruneState: function () {
      var steps = SFC.data.steps || [];
      var changed = true;
      var guard = 0;

      while (changed && guard < 10) {
        changed = false;
        guard += 1;

        steps.forEach(function (step) {
          var visible = SFC.steps.isStepVisible(step);

          if (step.type === 'custom-dimensions') {
            if (!visible && (SFC.state.customWidthMm != null || SFC.state.customLengthMm != null)) {
              delete SFC.state.customWidthMm;
              delete SFC.state.customLengthMm;
              changed = true;
            }
            return;
          }

          if (step.type === 'number' && step.field) {
            if (!visible && SFC.state[step.field] != null) {
              delete SFC.state[step.field];
              changed = true;
            }
            return;
          }

          if (step.type !== 'options' || !step.field || SFC.state[step.field] == null) {
            return;
          }

          if (!visible) {
            delete SFC.state[step.field];
            changed = true;
            return;
          }

          if (!SFC.steps.optionsForStep(step)[SFC.state[step.field]]) {
            delete SFC.state[step.field];
            changed = true;
          }
        });
      }
    },

    handleOptionClick: function (field, value) {
      SFC.state[field] = value;
      SFC.quote = null;

      SFC.events.pruneState();

      SFC.render.page();
      clearTimeout(SFC.quoteTimer);

      var step = SFC.events.stepForField(field);
      if (step && step.quoteImmediate) {
        SFC.quoteApi.fetch();
        return;
      }

      SFC.quoteApi.schedule();
    },

    handleQuantityInput: function () {
      var raw = String($('#sfc-quantity').val());
      if (raw === '') {
        delete SFC.state.quantity;
        SFC.quoteApi.schedule();
        return;
      }
      var value = parseInt(raw, 10);
      if (isNaN(value)) {
        return;
      }
      SFC.state.quantity = value;
      SFC.quoteApi.schedule();
    },

    handlePagesInput: function () {
      var raw = String($('#sfc-pages').val());
      if (raw === '') {
        delete SFC.state.pages;
        SFC.quoteApi.schedule();
        return;
      }
      var value = parseInt(raw, 10);
      if (isNaN(value)) {
        return;
      }
      SFC.state.pages = value;
      SFC.quoteApi.schedule();
    },

    handleInnerPagesInput: function () {
      var raw = String($('#sfc-inner-pages').val());
      if (raw === '') {
        delete SFC.state.innerPages;
        SFC.quoteApi.schedule();
        return;
      }
      var value = parseInt(raw, 10);
      if (isNaN(value)) {
        return;
      }
      SFC.state.innerPages = value;
      SFC.quoteApi.schedule();
    },

    handleDecimalNumberInput: function (inputId, field) {
      var raw = String($('#' + inputId).val());
      if (raw === '') {
        delete SFC.state[field];
        SFC.quoteApi.schedule();
        return;
      }
      var value = parseFloat(raw);
      if (isNaN(value)) {
        return;
      }
      SFC.state[field] = value;
      SFC.quoteApi.schedule();
    },

    handleCustomDimensionsInput: function () {
      var widthRaw = String($('#sfc-custom-width').val());
      var lengthRaw = String($('#sfc-custom-length').val());

      if (widthRaw === '') {
        delete SFC.state.customWidthMm;
      } else {
        var width = parseFloat(widthRaw);
        if (!isNaN(width)) {
          SFC.state.customWidthMm = width;
        }
      }

      if (lengthRaw === '') {
        delete SFC.state.customLengthMm;
      } else {
        var length = parseFloat(lengthRaw);
        if (!isNaN(length)) {
          SFC.state.customLengthMm = length;
        }
      }

      SFC.quoteApi.schedule();
    },

    postAddToCart: function () {
      return $.post(SFC.data.ajaxUrl, {
        action: 'sfc_add_product_to_cart',
        nonce: SFC.data.nonce,
        product_data: JSON.stringify({
          productSlug: SFC.data.productSlug,
          product_id: SFC.data.wooProductId,
          state: SFC.state,
          calculated_price: SFC.quote.totalPrice,
        }),
      });
    },

    isExpiredNonceResponse: function (payload) {
      return !!(payload && payload.data && payload.data.code === 'invalid_nonce');
    },

    // Pages served from a full-page cache can carry an expired nonce; fetch a
    // fresh one and retry the request exactly once before surfacing an error.
    retryWithFreshNonce: function (postFn, onSuccess, showError) {
      $.post(SFC.data.ajaxUrl, { action: 'sfc_refresh_public_nonce' })
        .done(function (res) {
          if (!res.success || !res.data || !res.data.nonce) {
            showError(null);
            return;
          }
          SFC.data.nonce = res.data.nonce;
          postFn()
            .done(function (retryRes) {
              if (retryRes.success) {
                onSuccess(retryRes);
                return;
              }
              showError(retryRes);
            })
            .fail(function (xhr) {
              showError(xhr && xhr.responseJSON);
            });
        })
        .fail(function () {
          showError(null);
        });
    },

    // Post via postFn; on success call onSuccess; recover once from an
    // expired nonce; everything else goes to showError.
    postWithNonceRecovery: function (postFn, onSuccess, showError) {
      postFn()
        .done(function (res) {
          if (res.success) {
            onSuccess(res);
            return;
          }
          if (SFC.events.isExpiredNonceResponse(res)) {
            SFC.events.retryWithFreshNonce(postFn, onSuccess, showError);
            return;
          }
          showError(res);
        })
        .fail(function (xhr) {
          var json = xhr && xhr.responseJSON;
          if (SFC.events.isExpiredNonceResponse(json)) {
            SFC.events.retryWithFreshNonce(postFn, onSuccess, showError);
            return;
          }
          showError(json);
        });
    },

    handleAddToCart: function () {
      if (!SFC.quote || !SFC.data.wooProductId || SFC.isAdding) {
        return;
      }

      SFC.isAdding = true;
      var $btn = $('#sfc-add-to-cart').prop('disabled', true).text(SFC.strings.adding_to_cart || 'Agregando…');

      var finish = function () {
        SFC.isAdding = false;
        $btn.prop('disabled', false).text(SFC.strings.add_to_cart || 'Agregar al carrito');
      };

      var showError = function (payload) {
        SFC.quoteApi.showError(
          (payload && payload.data && payload.data.message) ||
            SFC.strings.cart_error ||
            'No se pudo agregar al carrito.'
        );
        finish();
      };

      var onSuccess = function (res) {
        if (res.data && res.data.cart_url) {
          window.location.href = res.data.cart_url;
          return;
        }
        showError(res);
      };

      SFC.events.postWithNonceRecovery(SFC.events.postAddToCart, onSuccess, showError);
    },

    postSaveQuote: function () {
      return $.post(SFC.data.ajaxUrl, {
        action: 'sfc_save_quote',
        nonce: SFC.data.nonce,
        product_slug: SFC.data.productSlug,
        state: JSON.stringify(SFC.state),
      });
    },

    showShareLink: function (url) {
      var strings = SFC.strings;
      var esc = SFC.utils.esc;

      $('#sfc-share-link').html(
        '<span class="sfc__share-label">' +
          esc(strings.quote_saved_share || 'Comparta este enlace:') +
          '</span>' +
          '<input type="text" readonly id="sfc-share-url" class="sfc__input sfc__share-input" value="' +
          esc(url) +
          '" />' +
          '<span id="sfc-share-copied" class="sfc__share-copied" style="display:none;">' +
          esc(strings.quote_saved_copied || '') +
          '</span>'
      );

      if (window.navigator && navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(
          function () {
            $('#sfc-share-copied').show();
          },
          function () {}
        );
      }
    },

    handleSaveQuote: function () {
      if (!SFC.quote || SFC.isSavingQuote) {
        return;
      }

      SFC.isSavingQuote = true;
      var $btn = $('#sfc-save-quote')
        .prop('disabled', true)
        .text(SFC.strings.saving_quote || 'Guardando…');

      var finish = function () {
        SFC.isSavingQuote = false;
        $btn.prop('disabled', !SFC.quote).text(SFC.strings.save_quote || 'Guardar cotización');
      };

      var showError = function (payload) {
        SFC.quoteApi.showError(
          (payload && payload.data && payload.data.message) ||
            SFC.strings.quote_save_error ||
            'No se pudo guardar la cotización.'
        );
        finish();
      };

      var onSuccess = function (res) {
        if (res.data && res.data.url) {
          SFC.events.showShareLink(res.data.url);
          finish();
          return;
        }
        showError(res);
      };

      SFC.events.postWithNonceRecovery(SFC.events.postSaveQuote, onSuccess, showError);
    },

    bind: function () {},

    bindDelegated: function () {
      var root = $(SFC.root);

      root.on('click', '[data-field]', function () {
        SFC.events.handleOptionClick($(this).attr('data-field'), $(this).attr('data-value'));
      });

      root.on('input', '#sfc-quantity', function () {
        SFC.events.handleQuantityInput();
      });

      root.on('input', '#sfc-inner-pages', function () {
        SFC.events.handleInnerPagesInput();
      });

      root.on('input', '#sfc-pages', function () {
        SFC.events.handlePagesInput();
      });

      root.on('input', '#sfc-diameter', function () {
        SFC.events.handleDecimalNumberInput('sfc-diameter', 'diameterMm');
      });

      root.on('input', '#sfc-custom-width, #sfc-custom-length', function () {
        SFC.events.handleCustomDimensionsInput();
      });

      root.on('click', '#sfc-add-to-cart', function () {
        SFC.events.handleAddToCart();
      });

      root.on('click', '#sfc-save-quote', function () {
        SFC.events.handleSaveQuote();
      });

      root.on('focus click', '#sfc-share-url', function () {
        this.select();
      });

    },
  };
})(window, jQuery);
