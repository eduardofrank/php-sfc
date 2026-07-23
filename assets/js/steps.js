(function (window) {
  'use strict';

  var SFC = window.SFC;
  var utils = SFC.utils;

  // Generic step renderer (Phase 2). Steps arrive as declarative descriptors
  // in SFC.data.steps, built server-side from the product config — this file
  // contains no per-product logic.
  SFC.steps = {
    conditionMet: function (condition) {
      if (!condition || !condition.field) {
        return true;
      }

      var value = SFC.state[condition.field];
      var key = value == null ? null : String(value);

      if (condition.in) {
        return key != null && condition.in.indexOf(key) !== -1;
      }

      if (condition.notIn) {
        return key == null || condition.notIn.indexOf(key) === -1;
      }

      return true;
    },

    groupMet: function (conditions) {
      var list = conditions || [];
      var i;

      for (i = 0; i < list.length; i++) {
        if (!SFC.steps.conditionMet(list[i])) {
          return false;
        }
      }

      return true;
    },

    isStepVisible: function (step) {
      if (step.visibleWhen && !SFC.steps.groupMet(step.visibleWhen)) {
        return false;
      }

      if (step.visibleWhenAny) {
        var groups = step.visibleWhenAny;
        var i;
        for (i = 0; i < groups.length; i++) {
          if (SFC.steps.groupMet(groups[i])) {
            return true;
          }
        }
        return false;
      }

      return true;
    },

    optionsForStep: function (step) {
      var source = SFC.data[step.optionsFrom] || {};

      if (step.optionsByField) {
        var selected = SFC.state[step.optionsByField];
        return (selected != null && source[selected]) || {};
      }

      return source;
    },

    stepLabel: function (step) {
      return SFC.strings[step.labelKey] || step.labelFallback || '';
    },

    helpTemplateVars: function (step) {
      return {
        min: step.min != null ? step.min : SFC.data.minQuantity,
        max: step.max,
        units: SFC.stateApi.selectedUnitsPerSheet(),
      };
    },

    helpParagraphs: function (step) {
      var keys = step.helpKeys || [];
      var html = '';
      var i;

      for (i = 0; i < keys.length; i++) {
        var template = SFC.strings[keys[i]];
        if (!template) {
          continue;
        }
        html +=
          '<p class="sfc__help">' +
          utils.esc(utils.formatTemplate(template, SFC.steps.helpTemplateVars(step))) +
          '</p>';
      }

      return html;
    },

    noticeParagraph: function (step) {
      if (!step.noticeKey || !SFC.strings[step.noticeKey]) {
        return '';
      }

      if (step.noticeWhen && !SFC.steps.groupMet(step.noticeWhen)) {
        return '';
      }

      return (
        '<p class="sfc__help sfc__help--notice">' +
        utils.esc(SFC.strings[step.noticeKey]) +
        '</p>'
      );
    },

    optionButtons: function (items, selectedKey, field) {
      return Object.keys(items)
        .map(function (key) {
          var item = items[key];
          var selected = key === selectedKey ? ' sfc__option--selected' : '';
          var label = utils.formatOptionLabel(item.label);
          var stacked = label.stacked ? ' sfc__option--stacked' : '';
          return (
            '<button type="button" class="sfc__option' +
            stacked +
            selected +
            '" data-field="' +
            utils.esc(field) +
            '" data-value="' +
            utils.esc(key) +
            '">' +
            label.html +
            '</button>'
          );
        })
        .join('');
    },

    renderSection: function (label, body, items, trailingHtml) {
      var optionsClass = 'sfc__options';
      if (utils.shouldUseSingleOptionRow(items)) {
        optionsClass += ' sfc__options--single-row';
      }

      return (
        '<div class="sfc__section"><span class="sfc__label">' +
        utils.esc(label) +
        '</span><div class="' +
        optionsClass +
        '">' +
        body +
        '</div>' +
        (trailingHtml || '') +
        '</div>'
      );
    },

    renderCustomDimensionFields: function (step) {
      var strings = SFC.strings;
      var limits = SFC.data.customDimensionLimits || {};
      var minDim = Math.min(limits.minWidthMm || 50, limits.minHeightMm || 50);
      var maxDim = Math.max(limits.maxWidthMm || 450, limits.maxHeightMm || 310);
      var widthVal =
        SFC.state.customWidthMm != null && SFC.state.customWidthMm !== ''
          ? SFC.state.customWidthMm
          : '';
      var lengthVal =
        SFC.state.customLengthMm != null && SFC.state.customLengthMm !== ''
          ? SFC.state.customLengthMm
          : '';
      var mmHint = strings.step_custom_dimension_mm_hint || 'En milímetros';
      var helpHtml = '';

      if (step && step.helpKeys && step.helpKeys.length) {
        helpHtml = SFC.steps.helpParagraphs(step);
      } else if (strings.size_custom_dimensions_help) {
        helpHtml =
          '<p class="sfc__help">' + utils.esc(strings.size_custom_dimensions_help) + '</p>';
      }

      function dimField(labelKey, fallback, inputId, value) {
        return (
          '<div class="sfc__dim-field">' +
          '<div class="sfc__dim-heading">' +
          '<span class="sfc__dim-label">' +
          utils.esc(strings[labelKey] || fallback) +
          '</span>' +
          '<span class="sfc__dim-hint">' +
          utils.esc(mmHint) +
          '</span>' +
          '</div>' +
          '<input id="' +
          utils.esc(inputId) +
          '" class="sfc__input" type="number" min="' +
          utils.esc(minDim) +
          '" max="' +
          utils.esc(maxDim) +
          '" step="0.1" value="' +
          utils.esc(value) +
          '" /></div>'
        );
      }

      return (
        '<div class="sfc__dims">' +
        dimField('step_custom_width', 'Dimensión 1', 'sfc-custom-width', widthVal) +
        dimField('step_custom_length', 'Dimensión 2', 'sfc-custom-length', lengthVal) +
        '</div>' +
        helpHtml +
        (strings.size_custom_warning
          ? '<p class="sfc__help sfc__help--notice">' +
            utils.esc(strings.size_custom_warning) +
            '</p>'
          : '')
      );
    },

    renderNumberStep: function (step) {
      var value =
        SFC.state[step.field] != null && SFC.state[step.field] !== ''
          ? SFC.state[step.field]
          : '';

      return (
        '<div class="sfc__section"><span class="sfc__label">' +
        utils.esc(SFC.steps.stepLabel(step)) +
        '</span><input id="' +
        utils.esc(step.inputId || 'sfc-' + step.field) +
        '" class="sfc__input" type="number"' +
        (step.min != null ? ' min="' + utils.esc(step.min) + '"' : '') +
        (step.max != null ? ' max="' + utils.esc(step.max) + '"' : '') +
        ' step="' +
        utils.esc(step.step != null ? step.step : 1) +
        '" value="' +
        utils.esc(value) +
        '" />' +
        SFC.steps.helpParagraphs(step) +
        '</div>'
      );
    },

    renderOptionsStep: function (step) {
      var items = SFC.steps.optionsForStep(step);
      if (!utils.hasOptions(items)) {
        return '';
      }

      return SFC.steps.renderSection(
        SFC.steps.stepLabel(step),
        SFC.steps.optionButtons(items, SFC.state[step.field], step.field),
        items,
        SFC.steps.noticeParagraph(step) + SFC.steps.helpParagraphs(step)
      );
    },

    renderStaticOptionStep: function (step) {
      var text = SFC.data[step.textFrom];
      if (!text) {
        return '';
      }

      return SFC.steps.renderSection(
        SFC.steps.stepLabel(step),
        '<button type="button" class="sfc__option sfc__option--selected" disabled>' +
          utils.esc(text) +
          '</button>'
      );
    },

    renderStep: function (step) {
      switch (step.type) {
        case 'options':
          return SFC.steps.renderOptionsStep(step);
        case 'number':
          return SFC.steps.renderNumberStep(step);
        case 'custom-dimensions':
          var dimBody = SFC.steps.renderCustomDimensionFields(step);
          if (step.labelKey || step.labelFallback) {
            return (
              '<div class="sfc__section"><span class="sfc__label">' +
              utils.esc(SFC.steps.stepLabel(step)) +
              '</span>' +
              dimBody +
              '</div>'
            );
          }
          return dimBody;
        case 'static-option':
          return SFC.steps.renderStaticOptionStep(step);
        default:
          return '';
      }
    },

    buildSections: function () {
      return (SFC.data.steps || [])
        .filter(SFC.steps.isStepVisible)
        .map(SFC.steps.renderStep)
        .join('');
    },
  };
})(window);
