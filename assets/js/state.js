(function (window) {
  'use strict';

  var SFC = window.SFC;

  SFC.stateApi = {
    // Quote readiness is derived from the declarative steps: every visible
    // required step must hold a valid value.
    isQuoteReady: function () {
      var steps = SFC.data.steps || [];
      var i;

      for (i = 0; i < steps.length; i++) {
        var step = steps[i];

        if (!step.required || !SFC.steps.isStepVisible(step)) {
          continue;
        }

        if (!SFC.stateApi.stepHasValidValue(step)) {
          return false;
        }
      }

      return true;
    },

    stepHasValidValue: function (step) {
      switch (step.type) {
        case 'options':
          var value = SFC.state[step.field];
          if (value == null || value === '') {
            return false;
          }
          return !!SFC.steps.optionsForStep(step)[value];

        case 'number':
          var rawNum = SFC.state[step.field];
          if (rawNum == null || rawNum === '') {
            return false;
          }
          var num =
            step.step != null && step.step % 1 !== 0
              ? parseFloat(rawNum)
              : parseInt(rawNum, 10);
          if (isNaN(num)) {
            return false;
          }
          if (step.min != null && num < step.min) {
            return false;
          }
          if (step.max != null && num > step.max) {
            return false;
          }
          if (step.multipleOf && num % step.multipleOf !== 0) {
            return false;
          }
          return true;

        case 'custom-dimensions':
          var width = parseFloat(SFC.state.customWidthMm);
          var length = parseFloat(SFC.state.customLengthMm);
          return !isNaN(width) && !isNaN(length) && width > 0 && length > 0;

        default:
          return true;
      }
    },

    selectedUnitsPerSheet: function () {
      var size = SFC.data.sizes && SFC.data.sizes[SFC.state.size];
      if (size && size.unitsPerSheet) {
        return size.unitsPerSheet;
      }
      if (SFC.quote && SFC.quote.layoutViz && SFC.quote.layoutViz.unitsPerSheet) {
        return SFC.quote.layoutViz.unitsPerSheet;
      }
      return 2;
    },
  };
})(window);
