(function (window) {
  'use strict';

  var SFC = window.SFC;
  var utils = SFC.utils;
  var stateApi = SFC.stateApi;

  SFC.render = {
    collectImpositionWarnings: function () {
      if (!SFC.quote) {
        return [];
      }

      var byCode = {};
      var sources = [
        SFC.quote.imposition && SFC.quote.imposition.warnings,
        SFC.quote.layoutViz && SFC.quote.layoutViz.warnings,
      ];
      var skipFlatWaste = !!(SFC.data && SFC.data.suppressImpositionWasteUi);
      var flatWasteCodes = { job_waste: true, denser_grid_reduces_waste: true };
      var i;
      var j;

      for (i = 0; i < sources.length; i++) {
        var warnings = sources[i] || [];
        for (j = 0; j < warnings.length; j++) {
          var warning = warnings[j];
          if (warning && warning.code) {
            if (skipFlatWaste && flatWasteCodes[warning.code]) {
              continue;
            }
            byCode[warning.code] = warning;
          }
        }
      }

      return Object.keys(byCode).map(function (key) {
        return byCode[key];
      });
    },

    pressOrientationNote: function (quote, className) {
      var strings = SFC.strings;
      var orientation = utils.customSizePressOrientation(quote);

      if (!orientation) {
        return '';
      }

      return (
        '<span class="' +
        (className || 'sfc__press-orientation') +
        '">' +
        utils.esc(utils.formatTemplate(strings.layout_press_orientation, orientation)) +
        '</span>'
      );
    },

    warnings: function () {
      var warnings = SFC.render.collectImpositionWarnings();
      if (!warnings.length) {
        return '';
      }

      var strings = SFC.strings;
      var items = warnings
        .map(function (warning) {
          var severity = warning.severity === 'info' ? 'info' : 'warning';
          return (
            '<li class="sfc__warning sfc__warning--' +
            severity +
            '">' +
            utils.esc(warning.message || warning.code) +
            '</li>'
          );
        })
        .join('');

      return (
        '<div class="sfc__warnings">' +
        '<span class="sfc__label">' +
        utils.esc(strings.imposition_warnings_title || 'Avisos de montaje') +
        '</span>' +
        '<ul class="sfc__warnings-list">' +
        items +
        '</ul></div>'
      );
    },

    layoutViz: function () {
      var quote = SFC.quote;
      var strings = SFC.strings;

      if (!quote || !quote.layoutViz) {
        return '';
      }

      var viz = quote.layoutViz;
      var imposition = quote.imposition || {};
      var blocks = [];
      var slots = viz.unitsPerSheet || 0;
      var filledOnPreview = Math.min(slots, imposition.unitsOnLastSheet || slots);
      var sheetWidthMm = viz.printableWidthMm || 450;
      var sheetHeightMm = viz.printableHeightMm || 310;
      var unitWidthMm = viz.unitWidthMm || 1;
      var unitHeightMm = viz.unitHeightMm || 1;
      var gapMm = viz.gapMm != null ? viz.gapMm : 4;
      var offsetLeftMm = viz.layoutOffsetLeftMm || 0;
      var offsetTopMm = viz.layoutOffsetTopMm || 0;
      var cols = viz.cols || 1;
      var i;

      for (i = 0; i < slots; i++) {
        var filled = i < filledOnPreview;
        var col = i % cols;
        var row = Math.floor(i / cols);
        var leftMm = offsetLeftMm + col * (unitWidthMm + gapMm);
        var topMm = offsetTopMm + row * (unitHeightMm + gapMm);
        var leftPct = ((leftMm / sheetWidthMm) * 100).toFixed(4);
        var topPct = ((topMm / sheetHeightMm) * 100).toFixed(4);
        var widthPct = ((unitWidthMm / sheetWidthMm) * 100).toFixed(4);
        var heightPct = ((unitHeightMm / sheetHeightMm) * 100).toFixed(4);

        blocks.push(
          '<div class="sfc__viz-block' +
            (filled ? '' : ' sfc__viz-block--empty') +
            '" style="left:' +
            leftPct +
            '%;top:' +
            topPct +
            '%;width:' +
            widthPct +
            '%;height:' +
            heightPct +
            '%;">' +
            (filled ? utils.esc(strings.units_label || 'Hoja') : '') +
            '</div>'
        );
      }

      var captionKey = viz.captionKey || 'layout_caption';
      var captionTemplate = strings[captionKey] || strings.layout_caption;
      var caption = utils.formatTemplate(captionTemplate, { units: viz.unitsPerSheet });
      var warn = '';
      if (filledOnPreview < slots && !SFC.data.suppressImpositionWasteUi) {
        warn =
          '<span class="sfc__viz-warn">' +
          utils.esc(
            utils.formatTemplate(strings.layout_last_row, {
              filled: filledOnPreview,
              total: slots,
            })
          ) +
          '</span>';
      }

      var orientationNote = SFC.render.pressOrientationNote(quote, 'sfc__viz-caption sfc__viz-orientation');

      return (
        '<div class="sfc__viz-wrap">' +
        '<div class="sfc__viz-header"><span>' +
        utils.esc(strings.layout_title || 'Vista previa del montaje') +
        '</span><span>' +
        viz.printableWidthMm +
        ' × ' +
        viz.printableHeightMm +
        ' mm</span></div>' +
        '<div class="sfc__viz-track">' +
        '<span class="sfc__viz-edge sfc__viz-edge--left"></span>' +
        '<span class="sfc__viz-edge sfc__viz-edge--right"></span>' +
        '<div class="sfc__viz-sheet" style="aspect-ratio:' +
        sheetWidthMm +
        ' / ' +
        sheetHeightMm +
        ';">' +
        '<div class="sfc__viz-layout">' +
        blocks.join('') +
        '</div></div></div>' +
        '<span class="sfc__viz-caption">' +
        utils.esc(caption) +
        '</span>' +
        orientationNote +
        warn +
        '</div>'
      );
    },

    summary: function () {
      var strings = SFC.strings;
      var quote = SFC.quote;

      if (!quote) {
        return (
          '<div class="sfc__summary-row"><span>' +
          utils.esc(strings.total_label || 'Total') +
          '</span><strong>—</strong></div>'
        );
      }

      var pressOrientation = utils.customSizePressOrientation(quote);
      var pressOrientationRow = '';
      var pricing = quote.pricing || {};
      var tradeRows = '';
      var addonRows = '';

      if (pricing.dieCutAmount) {
        addonRows +=
          '<div class="sfc__summary-row"><span>' +
          utils.esc(strings.die_cut_label || 'Troquelado') +
          '</span><strong>' +
          utils.money(pricing.dieCutAmount) +
          '</strong></div>';
      }

      if (pricing.hardcoverAmount) {
        addonRows +=
          '<div class="sfc__summary-row"><span>' +
          utils.esc(strings.hardcover_label || 'Tapa dura') +
          '</span><strong>' +
          utils.money(pricing.hardcoverAmount) +
          '</strong></div>';
      }

      if (pricing.tradeDiscountAmount) {
        tradeRows =
          '<div class="sfc__summary-row"><span>' +
          utils.esc(strings.trade_list_price_label || 'Precio de lista') +
          '</span><strong>' +
          utils.money(pricing.listTotalPrice) +
          '</strong></div>' +
          '<div class="sfc__summary-row"><span>' +
          utils.esc(strings.trade_discount_label || 'Descuento mayorista') +
          ' (' +
          utils.esc(pricing.tradeDiscountPct) +
          '%)</span><strong>−' +
          utils.money(pricing.tradeDiscountAmount) +
          '</strong></div>';
      }

      if (pressOrientation) {
        pressOrientationRow =
          '<div class="sfc__summary-row sfc__summary-row--note"><span>' +
          utils.esc(strings.summary_press_orientation_label || 'Montaje en hoja') +
          '</span><strong>' +
          pressOrientation.width +
          ' × ' +
          pressOrientation.length +
          ' mm</strong></div>';
      }

      if (quote.jobType === 'booklet') {
        return (
          pressOrientationRow +
          '<div class="sfc__summary-row"><span>' +
          utils.esc(strings.units_label || 'Ejemplares') +
          '</span><strong>' +
          quote.unitQuantity +
          '</strong></div>' +
          '<div class="sfc__summary-row"><span>' +
          utils.esc(strings.inner_sheets_label || 'Hojas de impresión — tripa') +
          '</span><strong>' +
          (quote.innerSheetQuantity != null ? quote.innerSheetQuantity : '—') +
          '</strong></div>' +
          '<div class="sfc__summary-row"><span>' +
          utils.esc(strings.cover_sheets_label || 'Hojas de impresión — portada') +
          '</span><strong>' +
          (quote.coverSheetQuantity != null ? quote.coverSheetQuantity : '—') +
          '</strong></div>' +
          '<div class="sfc__summary-row"><span>' +
          utils.esc(strings.sheets_total_label || strings.sheets_label || 'Total hojas de impresión') +
          '</span><strong>' +
          quote.sheetQuantity +
          '</strong></div>' +
          '<div class="sfc__summary-row"><span>' +
          utils.esc(strings.unit_price_label || 'Precio por hoja de impresión') +
          '</span><strong>' +
          utils.money(quote.unitPrice) +
          '</strong></div>' +
          addonRows +
          tradeRows +
          '<div class="sfc__total"><span>' +
          utils.esc(strings.total_label || 'Total') +
          '</span><span class="sfc__total-amount">' +
          utils.money(quote.totalPrice) +
          '</span></div>'
        );
      }

      if (quote.jobType === 'album') {
        return (
          pressOrientationRow +
          '<div class="sfc__summary-row"><span>' +
          utils.esc(strings.units_label || 'Álbumes') +
          '</span><strong>' +
          quote.unitQuantity +
          '</strong></div>' +
          '<div class="sfc__summary-row"><span>' +
          utils.esc(strings.pages_label || 'Páginas por álbum') +
          '</span><strong>' +
          (quote.album && quote.album.pages != null ? quote.album.pages : '—') +
          '</strong></div>' +
          '<div class="sfc__summary-row"><span>' +
          utils.esc(strings.sheets_label || 'Hojas de impresión') +
          '</span><strong>' +
          quote.sheetQuantity +
          '</strong></div>' +
          addonRows +
          tradeRows +
          '<div class="sfc__total"><span>' +
          utils.esc(strings.total_label || 'Total') +
          '</span><span class="sfc__total-amount">' +
          utils.money(quote.totalPrice) +
          '</span></div>'
        );
      }

      return (
        pressOrientationRow +
        '<div class="sfc__summary-row"><span>' +
        utils.esc(strings.units_label || 'Hojas membretadas') +
        '</span><strong>' +
        quote.unitQuantity +
        '</strong></div>' +
        '<div class="sfc__summary-row"><span>' +
        utils.esc(strings.sheets_label || 'Hojas de impresión') +
        '</span><strong>' +
        quote.sheetQuantity +
        '</strong></div>' +
        '<div class="sfc__summary-row"><span>' +
        utils.esc(strings.unit_price_label || 'Precio por hoja de impresión') +
        '</span><strong>' +
        utils.money(quote.unitPrice) +
        '</strong></div>' +
        addonRows +
        tradeRows +
        '<div class="sfc__total"><span>' +
        utils.esc(strings.total_label || 'Total') +
        '</span><span class="sfc__total-amount">' +
        utils.money(quote.totalPrice) +
        '</span></div>'
      );
    },

    artworkSection: function () {
      // The upload input lives on the cart page (see [sfc_cart_artwork]); the
      // product page only points customers there. Uploading against a real
      // cart line keeps the public upload endpoint out of reach of bare bots.
      var strings = SFC.strings;
      var esc = utils.esc;

      return (
        '<span class="sfc__label">' +
        esc(strings.artwork_label || 'Arte (opcional)') +
        '</span>' +
        '<p class="sfc__help sfc__artwork-hint">' +
        esc(strings.artwork_cart_hint || '') +
        '</p>'
      );
    },

    selectionNotice: function () {
      var strings = SFC.strings;

      if (stateApi.isQuoteReady() || !strings.selection_required) {
        return '';
      }

      return '<p class="sfc__help sfc__help--notice">' + utils.esc(strings.selection_required) + '</p>';
    },

    updateAfterQuote: function () {
      var data = SFC.data;
      var quote = SFC.quote;
      var $ = window.jQuery;
      var $root = $(SFC.root);

      if (!$root.find('#sfc-quote-summary').length) {
        SFC.render.page();
        return;
      }

      $('#sfc-selection-notice').html(SFC.render.selectionNotice());
      $('#sfc-quote-warnings').html(SFC.render.warnings());
      $('#sfc-quote-viz').html(SFC.render.layoutViz());
      $('#sfc-quote-summary').html(SFC.render.summary());
      $('#sfc-add-to-cart').prop('disabled', !quote || !data.wooProductId);
      $('#sfc-save-quote').prop('disabled', !quote);
      // Any quote change invalidates a previously shown share link.
      $('#sfc-share-link').empty();
    },

    page: function () {
      var strings = SFC.strings;
      var data = SFC.data;
      var quote = SFC.quote;
      var root = SFC.root;
      var sections = SFC.steps.buildSections();

      root.innerHTML =
        '<div class="sfc__main">' +
        '<div class="sfc__grid">' +
        '<div class="sfc__panel">' +
        '<h2 class="sfc__title">' +
        utils.esc(strings.product_title || 'Calculadora') +
        '</h2>' +
        (data.seedNotice
          ? '<p class="sfc__help sfc__help--notice sfc__seed-notice">' +
            utils.esc(data.seedNotice) +
            '</p>'
          : '') +
        '<div id="sfc-alert" style="display:none;" class="sfc__alert"></div>' +
        sections +
        '<div id="sfc-selection-notice">' +
        SFC.render.selectionNotice() +
        '</div>' +
        '<div id="sfc-quote-warnings">' +
        SFC.render.warnings() +
        '</div>' +
        '<div id="sfc-quote-viz">' +
        SFC.render.layoutViz() +
        '</div>' +
        '</div>' +
        '<div class="sfc__panel sfc__panel--sticky">' +
        '<h2 class="sfc__title">' +
        utils.esc(strings.summary_title || 'Tu cotización') +
        '</h2>' +
        '<div id="sfc-quote-summary">' +
        SFC.render.summary() +
        '</div>' +
        '<div id="sfc-artwork" class="sfc__artwork">' +
        SFC.render.artworkSection() +
        '</div>' +
        '<button type="button" id="sfc-add-to-cart" class="sfc__btn"' +
        (!quote || !data.wooProductId ? ' disabled' : '') +
        '>' +
        utils.esc(strings.add_to_cart || 'Agregar al carrito') +
        '</button>' +
        '<button type="button" id="sfc-save-quote" class="sfc__btn sfc__btn--secondary"' +
        (!quote ? ' disabled' : '') +
        '>' +
        utils.esc(strings.save_quote || 'Guardar cotización') +
        '</button>' +
        '<div id="sfc-share-link" class="sfc__share"></div>' +
        '</div></div></div>';
    },
  };
})(window);
