(function (window) {
  'use strict';

  window.SFC = window.SFC || {};

  window.SFC.root = null;
  window.SFC.data = null;
  window.SFC.strings = {};
  window.SFC.state = {};
  window.SFC.quote = null;
  window.SFC.quoteTimer = null;
  window.SFC.quoteRequestId = 0;
  window.SFC.isAdding = false;
  window.SFC.isSavingQuote = false;
})(window);
