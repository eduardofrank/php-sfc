(function (window) {
  'use strict';

  var SFC = window.SFC;

  SFC.utils = {
    esc: function (value) {
      return String(value == null ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    },

    money: function (value) {
      return '$' + Number(value || 0).toFixed(2);
    },

    formatTemplate: function (template, vars) {
      return String(template || '').replace(/\{(\w+)\}/g, function (_, key) {
        return vars[key] != null ? vars[key] : '';
      });
    },

    approxEqualMm: function (a, b) {
      return Math.abs(Number(a) - Number(b)) < 0.05;
    },

    formatDimMm: function (value) {
      var n = Number(value);
      if (!isFinite(n)) {
        return '—';
      }
      var rounded = Math.round(n * 10) / 10;
      return rounded % 1 === 0 ? String(rounded.toFixed(0)) : String(rounded);
    },

    isCustomSizePressRotated: function (quote) {
      if (!quote || !quote.size || quote.size.key !== 'custom') {
        return false;
      }

      var enteredW = quote.size.widthMm;
      var enteredH = quote.size.heightMm;
      var imposition = quote.imposition || quote.layoutViz || {};
      var pressW = imposition.unitWidthMm;
      var pressH = imposition.unitHeightMm;

      if (!pressW || !pressH) {
        return false;
      }

      if (
        SFC.utils.approxEqualMm(enteredW, pressW) &&
        SFC.utils.approxEqualMm(enteredH, pressH)
      ) {
        return false;
      }

      return (
        SFC.utils.approxEqualMm(enteredW, pressH) &&
        SFC.utils.approxEqualMm(enteredH, pressW)
      );
    },

    customSizePressOrientation: function (quote) {
      if (!SFC.utils.isCustomSizePressRotated(quote)) {
        return null;
      }

      var imposition = quote.imposition || quote.layoutViz || {};
      return {
        width: SFC.utils.formatDimMm(imposition.unitWidthMm),
        length: SFC.utils.formatDimMm(imposition.unitHeightMm),
      };
    },

    hasOptions: function (items) {
      return items && typeof items === 'object' && Object.keys(items).length > 0;
    },

    shouldUseSingleOptionRow: function (items) {
      return items && typeof items === 'object' && Object.keys(items).length > 1;
    },

    formatOptionLabel: function (label) {
      var text = String(label || '');
      var match = text.match(/^(.+?)\s+\(([^)]+)\)$/);

      if (!match) {
        return { html: SFC.utils.esc(text), stacked: false };
      }

      return {
        html:
          '<span class="sfc__option-primary">' +
          SFC.utils.esc(match[1]) +
          '</span><span class="sfc__option-secondary">(' +
          SFC.utils.esc(match[2]) +
          ')</span>',
        stacked: true,
      };
    },
  };
})(window);
