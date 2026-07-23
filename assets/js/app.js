(function (window, $) {
  'use strict';

  var SFC = window.SFC;
  var root = document.getElementById('sfc-root');

  if (!root || typeof window.__SFC_DATA === 'undefined') {
    if (root) {
      root.innerHTML =
        '<div class="sfc sfc--error"><p>La configuración de la calculadora no está disponible.</p></div>';
    }
    return;
  }

  SFC.root = root;
  SFC.data = window.__SFC_DATA;
  SFC.strings = SFC.data.strings || {};
  SFC.state = $.extend({}, SFC.data.defaults || {}, SFC.data.seedState || {});
  SFC.quote = null;
  SFC.quoteTimer = null;
  SFC.quoteRequestId = 0;
  SFC.isAdding = false;

  // A restored configuration (reorder or saved quote) may reference options
  // that no longer exist; drop those before first render.
  if (SFC.data.seedState) {
    SFC.events.pruneState();
  }

  SFC.render.page();
  SFC.events.bindDelegated();
  SFC.quoteApi.fetch();
})(window, jQuery);
