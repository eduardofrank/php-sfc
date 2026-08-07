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

          if (step.type === 'checkboxes' && step.field) {
            var picked = SFC.state[step.field];
            if (picked == null) {
              return;
            }
            if (!(picked instanceof Array)) {
              delete SFC.state[step.field];
              changed = true;
              return;
            }
            if (!visible) {
              if (picked.length) {
                SFC.state[step.field] = [];
                changed = true;
              }
              return;
            }
            var opts = SFC.steps.optionsForStep(step);
            var kept = picked.filter(function (key) {
              return !!opts[key];
            });
            if (kept.length !== picked.length) {
              SFC.state[step.field] = kept;
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

    // Toggle a value in an array-valued (checkbox) state field, e.g. services.
    handleServiceToggle: function (field, value) {
      var list = SFC.state[field] instanceof Array ? SFC.state[field].slice() : [];
      var idx = list.indexOf(value);
      if (idx === -1) {
        list.push(value);
      } else {
        list.splice(idx, 1);
      }
      SFC.state[field] = list;
      SFC.quote = null;

      SFC.events.pruneState();

      SFC.render.page();
      clearTimeout(SFC.quoteTimer);
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

    // Reflect a draft summary into the persistent draft bar.
    updateDraftBar: function (summary) {
      var $bar = $('#sfc-draft-bar');
      if (!$bar.length || !summary) {
        return;
      }
      var base = $bar.attr('data-base') || '';
      $('#sfc-draft-count').text(summary.count);
      $('#sfc-draft-total').text(
        (summary.currency || 'USD') + ' $' + Number(summary.grandTotal || 0).toFixed(2)
      );
      if (summary.count > 0) {
        $bar.removeClass('hidden');
      } else {
        $bar.addClass('hidden');
      }
      // (base kept for future use, e.g. rebuilding the CTA href)
      void base;
    },

    handleAddToQuote: function () {
      if (!SFC.quote || SFC.isAddingToQuote) {
        return;
      }
      SFC.isAddingToQuote = true;

      var strings = SFC.strings;
      var esc = SFC.utils.esc;
      var $btn = $('#sfc-add-to-quote')
        .prop('disabled', true)
        .text(strings.adding_to_quote || 'Agregando…');

      var reset = function () {
        SFC.isAddingToQuote = false;
        $btn.prop('disabled', !SFC.quote).text(strings.add_to_quote || 'Agregar a la cotización');
      };

      $.post(SFC.data.ajaxUrl, {
        action: 'sfc_quote_add_item',
        product_slug: SFC.data.productSlug,
        state: JSON.stringify(SFC.state),
      })
        .done(function (res) {
          if (res && res.success) {
            SFC.events.updateDraftBar(res.data);
            var base = $('#sfc-draft-bar').attr('data-base') || '';
            $('#sfc-add-confirm').html(
              '<span class="sfc__added">' +
                esc(strings.added_to_quote || 'Agregado a la cotización ✓') +
                '</span> ' +
                '<a class="sfc__added-link" href="' + esc(base) + '/">' +
                esc(strings.view_quote || 'Ver cotización') +
                '</a>'
            );
          } else {
            SFC.quoteApi.showError(
              (res && res.data && res.data.message) || 'No se pudo agregar el ítem.'
            );
          }
        })
        .fail(function (xhr) {
          SFC.quoteApi.showError(
            (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) ||
              'No se pudo agregar el ítem.'
          );
        })
        .always(reset);
    },

    bind: function () {},

    bindDelegated: function () {
      var root = $(SFC.root);

      root.on('click', '[data-field]', function () {
        SFC.events.handleOptionClick($(this).attr('data-field'), $(this).attr('data-value'));
      });

      root.on('click', '[data-check-field]', function () {
        SFC.events.handleServiceToggle(
          $(this).attr('data-check-field'),
          $(this).attr('data-value')
        );
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

      root.on('click', '#sfc-add-to-quote', function () {
        SFC.events.handleAddToQuote();
      });
    },
  };
})(window, jQuery);
