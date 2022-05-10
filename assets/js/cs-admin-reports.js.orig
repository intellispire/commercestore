/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/js/admin/reports/index.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/js/admin/reports/charts/index.js":
/*!*************************************************!*\
  !*** ./assets/js/admin/reports/charts/index.js ***!
  \*************************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _line_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./line.js */ "./assets/js/admin/reports/charts/line.js");
/* harmony import */ var _pie_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./pie.js */ "./assets/js/admin/reports/charts/pie.js");
/* harmony import */ var _utils_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./utils.js */ "./assets/js/admin/reports/charts/utils.js");
/* global eddAdminReportsCharts */

/**
 * Internal dependencies.
 */


 // Access existing global `edd` variable, or create a new object.

window.edd = window.edd || {};
/**
 * Render a chart based on config.
 *
 * This function is attached to the `edd` property attached to the `window`.
 *
 * @param {Object} config Chart config.
 */

window.cs.renderChart = function (config) {
  var isPie = Object(_utils_js__WEBPACK_IMPORTED_MODULE_2__["isPieChart"])(config);
  Chart.defaults.global.pointHitDetectionRadius = 5;

  if (Object(_utils_js__WEBPACK_IMPORTED_MODULE_2__["isPieChart"])(config)) {
    Object(_pie_js__WEBPACK_IMPORTED_MODULE_1__["render"])(config);
  } else {
    Object(_line_js__WEBPACK_IMPORTED_MODULE_0__["render"])(config);
  }
};

/***/ }),

/***/ "./assets/js/admin/reports/charts/line.js":
/*!************************************************!*\
  !*** ./assets/js/admin/reports/charts/line.js ***!
  \************************************************/
/*! exports provided: render, tooltipConfig */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "render", function() { return render; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "tooltipConfig", function() { return tooltipConfig; });
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _commercestore_currency__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @commercestore/currency */ "./assets/js/packages/currency/src/index.js");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./utils */ "./assets/js/admin/reports/charts/utils.js");


function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }

/* global Chart */

/**
 * Internal dependencies.
 */


/**
 * Render a line chart.
 *
 * @param {Object} config Global chart config.
 * @return {Chart}
 */

var render = function render(config) {
  var target = config.target;
  var _config$dates = config.dates,
      utcOffset = _config$dates.utc_offset,
      hourByHour = _config$dates.hour_by_hour,
      dayByDay = _config$dates.day_by_day;
  var number = new _commercestore_currency__WEBPACK_IMPORTED_MODULE_1__["NumberFormat"]();

  var lineConfig = _objectSpread(_objectSpread({}, config), {}, {
    options: _objectSpread(_objectSpread({}, config.options), {}, {
      tooltips: tooltipConfig(config),
      scales: _objectSpread(_objectSpread({}, config.options.scales), {}, {
        yAxes: [_objectSpread(_objectSpread({}, config.options.scales.yAxes[0]), {}, {
          ticks: {
            callback: function callback(value, index, values) {
              return number.format(value);
            }
          }
        })],
        xAxes: [_objectSpread(_objectSpread({}, config.options.scales.xAxes[0]), {}, {
          time: _objectSpread(_objectSpread({}, config.options.scales.xAxes[0].time), {}, {
            parser: function parser(date) {
              // Use UTC for larger dataset averages.
              // Specifically this ensures month by month shows the start of the month
              // if the UTC offset is negative.
              if (!hourByHour && !dayByDay) {
                return moment.utc(date);
              } else {
                return moment(date).utcOffset(utcOffset);
              }
            }
          })
        })]
      })
    })
  }); // Render


  return new Chart(document.getElementById(target), lineConfig);
};
/**
 * Get custom tooltip config for line charts.
 *
 * @param {Object} config Global chart config.
 * @return {Object}
 */

var tooltipConfig = function tooltipConfig(config) {
  return _objectSpread(_objectSpread({}, _utils__WEBPACK_IMPORTED_MODULE_2__["toolTipBaseConfig"]), {}, {
    callbacks: {
      /**
       * Generate a label.
       *
       * @param {Object} t
       * @param {Object} d
       */
      label: function label(t, d) {
        var datasets = config.options.datasets;
        var datasetConfig = datasets[Object.keys(datasets)[t.datasetIndex]];
        var label = Object(_utils__WEBPACK_IMPORTED_MODULE_2__["getLabelWithTypeCondition"])(t.yLabel, datasetConfig);
        return "".concat(d.datasets[t.datasetIndex].label, ": ").concat(label);
      }
    }
  });
};

/***/ }),

/***/ "./assets/js/admin/reports/charts/pie.js":
/*!***********************************************!*\
  !*** ./assets/js/admin/reports/charts/pie.js ***!
  \***********************************************/
/*! exports provided: render, tooltipConfig */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "render", function() { return render; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "tooltipConfig", function() { return tooltipConfig; });
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./utils */ "./assets/js/admin/reports/charts/utils.js");


function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }

/* global Chart */

/**
 * Internal dependencies.
 */

/**
 * Render a line chart.
 *
 * @param {Object} config Global chart config.
 * @return {Chart}
 */

var render = function render(config) {
  var target = config.target; // Config tooltips.

  config.options.tooltips = tooltipConfig(config); // Render

  return new Chart(document.getElementById(target), config);
};
/**
 * Get custom tooltip config for pie charts.
 *
 * @param {Object} config Global chart config.
 * @return {Object}
 */

var tooltipConfig = function tooltipConfig(config) {
  return _objectSpread(_objectSpread({}, _utils__WEBPACK_IMPORTED_MODULE_1__["toolTipBaseConfig"]), {}, {
    callbacks: {
      /**
       * Generate a label.
       *
       * @param {Object} t
       * @param {Object} d
       */
      label: function label(t, d) {
        var datasets = config.options.datasets;
        var datasetConfig = datasets[Object.keys(datasets)[t.datasetIndex]];
        var dataset = d.datasets[t.datasetIndex];
        var total = dataset.data.reduce(function (previousValue, currentValue, currentIndex, array) {
          return previousValue + currentValue;
        });
        var currentValue = dataset.data[t.index];
        var label = Object(_utils__WEBPACK_IMPORTED_MODULE_1__["getLabelWithTypeCondition"])(currentValue, datasetConfig);
        var precentage = Math.floor(currentValue / total * 100 + 0.5);
        return "".concat(d.labels[t.index], ": ").concat(label, " (").concat(precentage, "%)");
      }
    }
  });
};

/***/ }),

/***/ "./assets/js/admin/reports/charts/utils.js":
/*!*************************************************!*\
  !*** ./assets/js/admin/reports/charts/utils.js ***!
  \*************************************************/
/*! exports provided: isPieChart, getLabelWithTypeCondition, toolTipBaseConfig */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "isPieChart", function() { return isPieChart; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getLabelWithTypeCondition", function() { return getLabelWithTypeCondition; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "toolTipBaseConfig", function() { return toolTipBaseConfig; });
/* harmony import */ var _commercestore_currency__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @commercestore/currency */ "./assets/js/packages/currency/src/index.js");
/**
 * Internal dependencies
 */

/**
 * Determine if a pie graph.
 *
 * @todo maybe pass from data?
 *
 * @param {Object} config Global chart config.
 * @return {Bool}
 */

var isPieChart = function isPieChart(config) {
  var type = config.type;
  return type === 'pie' || type === 'doughnut';
};
/**
 * Determine if a chart's dataset has a special conditional type.
 *
 * Currently just checks for currency.
 *
 * @param {string} label Current label.
 * @param {Object} config Global chart config.
 */

var getLabelWithTypeCondition = function getLabelWithTypeCondition(label, datasetConfig) {
  var newLabel = label;
  var type = datasetConfig.type;

  if ('currency' === type) {
    var currency = new _commercestore_currency__WEBPACK_IMPORTED_MODULE_0__["Currency"]();
    newLabel = currency.format(label);
  }

  return newLabel;
};
/**
 * Shared tooltip configuration.
 */

var toolTipBaseConfig = {
  enabled: false,
  mode: 'index',
  position: 'nearest',

  /**
   * Output a a custom tooltip.
   *
   * @param {Object} tooltip Tooltip data.
   */
  custom: function custom(tooltip) {
    // Tooltip element.
    var tooltipEl = document.getElementById('cs-chartjs-tooltip');

    if (!tooltipEl) {
      tooltipEl = document.createElement('div');
      tooltipEl.id = 'cs-chartjs-tooltip';
      tooltipEl.innerHTML = '<table></table>';

      this._chart.canvas.parentNode.appendChild(tooltipEl);
    } // Hide if no tooltip.


    if (tooltip.opacity === 0) {
      tooltipEl.style.opacity = 0;
      return;
    } // Set caret position.


    tooltipEl.classList.remove('above', 'below', 'no-transform');

    if (tooltip.yAlign) {
      tooltipEl.classList.add(tooltip.yAlign);
    } else {
      tooltipEl.classList.add('no-transform');
    }

    function getBody(bodyItem) {
      return bodyItem.lines;
    } // Set Text


    if (tooltip.body) {
      var titleLines = tooltip.title || [];
      var bodyLines = tooltip.body.map(getBody);
      var innerHtml = '<thead>';
      titleLines.forEach(function (title) {
        innerHtml += '<tr><th>' + title + '</th></tr>';
      });
      innerHtml += '</thead><tbody>';
      bodyLines.forEach(function (body, i) {
        var colors = tooltip.labelColors[i];
        var borderColor = colors.borderColor,
            backgroundColor = colors.backgroundColor; // Super dirty check to use the legend's color.

        var fill = borderColor;

        if (fill === 'rgb(230, 230, 230)' || fill === '#fff') {
          fill = backgroundColor;
        }

        var style = ["background: ".concat(fill), "border-color: ".concat(fill), 'border-width: 2px'];
        var span = '<span class="cs-chartjs-tooltip-key" style="' + style.join(';') + '"></span>';
        innerHtml += '<tr><td>' + span + body + '</td></tr>';
      });
      innerHtml += '</tbody>';
      var tableRoot = tooltipEl.querySelector('table');
      tableRoot.innerHTML = innerHtml;
    }

    var positionY = this._chart.canvas.offsetTop;
    var positionX = this._chart.canvas.offsetLeft; // Display, position, and set styles for font

    tooltipEl.style.opacity = 1;
    tooltipEl.style.left = positionX + tooltip.caretX + 'px';
    tooltipEl.style.top = positionY + tooltip.caretY + 'px';
    tooltipEl.style.fontFamily = tooltip._bodyFontFamily;
    tooltipEl.style.fontSize = tooltip.bodyFontSize + 'px';
    tooltipEl.style.fontStyle = tooltip._bodyFontStyle;
    tooltipEl.style.padding = tooltip.yPadding + 'px ' + tooltip.xPadding + 'px';
  }
};

/***/ }),

/***/ "./assets/js/admin/reports/formatting.js":
/*!***********************************************!*\
  !*** ./assets/js/admin/reports/formatting.js ***!
  \***********************************************/
/*! exports provided: eddLabelFormatter, eddLegendFormatterSales, eddLegendFormatterEarnings */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function(jQuery) {/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "eddLabelFormatter", function() { return eddLabelFormatter; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "eddLegendFormatterSales", function() { return eddLegendFormatterSales; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "eddLegendFormatterEarnings", function() { return eddLegendFormatterEarnings; });
var eddLabelFormatter = function eddLabelFormatter(label, series) {
  return '<div style="font-size:12px; text-align:center; padding:2px">' + label + '</div>';
};
var eddLegendFormatterSales = function eddLegendFormatterSales(label, series) {
  var slug = label.toLowerCase().replace(/\s/g, '-'),
      color = '<div class="cs-legend-color" style="background-color: ' + series.color + '"></div>',
      value = '<div class="cs-pie-legend-item">' + label + ': ' + Math.round(series.percent) + '% (' + eddFormatNumber(series.data[0][1]) + ')</div>',
      item = '<div id="' + series.cs_vars.id + slug + '" class="cs-legend-item-wrapper">' + color + value + '</div>';
  jQuery('#cs-pie-legend-' + series.cs_vars.id).append(item);
  return item;
};
var eddLegendFormatterEarnings = function eddLegendFormatterEarnings(label, series) {
  var slug = label.toLowerCase().replace(/\s/g, '-'),
      color = '<div class="cs-legend-color" style="background-color: ' + series.color + '"></div>',
      value = '<div class="cs-pie-legend-item">' + label + ': ' + Math.round(series.percent) + '% (' + eddFormatCurrency(series.data[0][1]) + ')</div>',
      item = '<div id="' + series.cs_vars.id + slug + '" class="cs-legend-item-wrapper">' + color + value + '</div>';
  jQuery('#cs-pie-legend-' + series.cs_vars.id).append(item);
  return item;
};
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery")))

/***/ }),

/***/ "./assets/js/admin/reports/index.js":
/*!******************************************!*\
  !*** ./assets/js/admin/reports/index.js ***!
  \******************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function($, jQuery) {/* harmony import */ var _formatting_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./formatting.js */ "./assets/js/admin/reports/formatting.js");
/* harmony import */ var _charts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./charts */ "./assets/js/admin/reports/charts/index.js");
/* global pagenow, postboxes */

/**
 * Internal dependencies.
 */

 // Enable reports meta box toggle states.

if (typeof postboxes !== 'undefined' && /cs-reports/.test(pagenow)) {
  postboxes.add_postbox_toggles(pagenow);
}
/**
 * Reports / Exports screen JS
 */


var CS_Reports = {
  init: function init() {
    this.meta_boxes();
    this.date_options();
    this.customers_export();
  },
  meta_boxes: function meta_boxes() {
    $('.cs-reports-wrapper .postbox .handlediv').remove();
    $('.cs-reports-wrapper .postbox').removeClass('closed'); // Use a timeout to ensure this happens after core binding

    setTimeout(function () {
      $('.cs-reports-wrapper .postbox .hndle').unbind('click.postboxes');
    }, 1);
  },
  date_options: function date_options() {
    // Show hide extended date options
    $('select.cs-graphs-date-options').on('change', function (event) {
      var select = $(this),
          date_range_options = select.parent().siblings('.cs-date-range-options');

      if ('other' === select.val()) {
        date_range_options.removeClass('screen-reader-text');
      } else {
        date_range_options.addClass('screen-reader-text');
      }
    });
  },
  customers_export: function customers_export() {
    // Show / hide Download option when exporting customers
    $('#cs_customer_export_download').change(function () {
      var $this = $(this),
          download_id = $('option:selected', $this).val(),
          customer_export_option = $('#cs_customer_export_option');

      if ('0' === $this.val()) {
        customer_export_option.show();
      } else {
        customer_export_option.hide();
      } // On Download Select, Check if Variable Prices Exist


      if (parseInt(download_id) !== 0) {
        var data = {
          action: 'cs_check_for_download_price_variations',
          download_id: download_id,
          all_prices: true
        };
        var price_options_select = $('.cs_price_options_select');
        $.post(ajaxurl, data, function (response) {
          price_options_select.remove();
          $('#cs_customer_export_download_chosen').after(response);
        });
      } else {
        price_options_select.remove();
      }
    });
  }
};
jQuery(document).ready(function ($) {
  CS_Reports.init();
});
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery"), __webpack_require__(/*! jquery */ "jquery")))

/***/ }),

/***/ "./assets/js/packages/currency/src/index.js":
/*!**************************************************!*\
  !*** ./assets/js/packages/currency/src/index.js ***!
  \**************************************************/
/*! exports provided: NumberFormat, Currency */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Currency", function() { return Currency; });
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _number_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./number.js */ "./assets/js/packages/currency/src/number.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "NumberFormat", function() { return _number_js__WEBPACK_IMPORTED_MODULE_3__["NumberFormat"]; });





function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }

/* global cs_vars */

/**
 * Internal dependencies
 */
 // Make Number directly accessible from package.


/**
 * Currency
 *
 * @class Currency
 */

var Currency = /*#__PURE__*/function () {
  /**
   * Creates configuration for currency formatting.
   *
   * @todo Validate configuration.
   *
   * @since 3.0
   *
   * @param {Object} config Currency configuration arguments.
   * @param {string} [config.currency=cs_vars.currency] Currency (USD, AUD, etc).
   * @param {string} [config.currencySymbol=cs_vars.currency_sign] Currency symbol ($, €, etc).
   * @param {string} [config.currencySymbolPosition=cs_vars.currency_pos] Currency symbol position (left or right).
   * @param {number} [config.decimalPlaces=cs_vars.currency_decimals] The number of decimals places to format to.
   * @param {string} [config.decimalSeparator=cs_vars.decimal_separator] The separator between the number and decimal.
   * @param {string} [config.thousandsSeparator=cs_vars.thousands_separator] Thousands separator.
   */
  function Currency() {
    var config = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, Currency);

    var _cs_vars = cs_vars,
        currency = _cs_vars.currency,
        currencySymbol = _cs_vars.currency_sign,
        currencySymbolPosition = _cs_vars.currency_pos,
        precision = _cs_vars.currency_decimals,
        decimalSeparator = _cs_vars.decimal_separator,
        thousandSeparator = _cs_vars.thousands_separator;
    this.config = _objectSpread({
      currency: currency,
      currencySymbol: currencySymbol,
      currencySymbolPosition: currencySymbolPosition,
      precision: precision,
      decimalSeparator: decimalSeparator,
      thousandSeparator: thousandSeparator
    }, config);
    this.number = new _number_js__WEBPACK_IMPORTED_MODULE_3__["NumberFormat"](this.config);
  }
  /**
   * Formats a number for currency display.
   *
   * @since 3.0
   *
   * @param {number} number Number to format.
   * @return {?string} A formatted string.
   */


  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(Currency, [{
    key: "format",
    value: function format(number) {
      var absint = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : true;
      var _this$config = this.config,
          currencySymbol = _this$config.currencySymbol,
          currencySymbolPosition = _this$config.currencySymbolPosition;
      var formattedNumber = this.number.format(number);
      var isNegative = number < 0;
      var currency = ''; // Turn a negative value positive so we can put &ndash; before
      // currency symbol if needed.

      if (true === isNegative && true === absint) {
        formattedNumber = this.number.format(number * -1);
      }

      switch (currencySymbolPosition) {
        case 'before':
          currency = currencySymbol + formattedNumber;
          break;

        case 'after':
          currency = formattedNumber + currencySymbol;
          break;
      } // Place negative symbol before currency symbol if needed.


      if (true === isNegative && false === absint) {
        currency = "-".concat(currency);
      }

      return currency;
    }
    /**
     * Removes formatting from a currency string.
     *
     * @since 3.0
     *
     * @param {string} currency String containing currency formatting.
     * @return {number} Unformatted number.
     */

  }, {
    key: "unformat",
    value: function unformat(currency) {
      var currencySymbol = this.config.currencySymbol; // Remove any existing currency symbol.

      var number = currency.replace(currencySymbol, '');
      return this.number.unformat(number);
    }
  }]);

  return Currency;
}();

/***/ }),

/***/ "./assets/js/packages/currency/src/number.js":
/*!***************************************************!*\
  !*** ./assets/js/packages/currency/src/number.js ***!
  \***************************************************/
/*! exports provided: NumberFormat */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "NumberFormat", function() { return NumberFormat; });
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__);




function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }

/* global cs_vars */

/**
 * External dependencies
 */
var numberFormatter = __webpack_require__(/*! locutus/php/strings/number_format */ "./node_modules/locutus/php/strings/number_format.js");
/**
 * NumberFormat
 *
 * @class NumberFormat
 */


var NumberFormat = /*#__PURE__*/function () {
  /**
   * Creates configuration for number formatting.
   *
   * @todo Validate configuration.
   * @since 3.0
   * @param {Object} config Configuration for the number formatter.
   * @param {number} [config.decimalPlaces=cs_vars.currency_decimals] The number of decimals places to format to.
   * @param {string} [config.decimalSeparator=cs_vars.decimal_separator] The separator between the number and decimal.
   * @param {string} [config.thousandsSeparator=cs_vars.thousands_separator] Thousands separator.
   */
  function NumberFormat() {
    var config = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, NumberFormat);

    var _cs_vars = cs_vars,
        precision = _cs_vars.currency_decimals,
        decimalSeparator = _cs_vars.decimal_separator,
        thousandSeparator = _cs_vars.thousands_separator;
    this.config = _objectSpread({
      precision: precision,
      decimalSeparator: decimalSeparator,
      thousandSeparator: thousandSeparator
    }, config);
  }
  /**
   * Formats a number for display based on decimal settings.
   *
   * @since 3.0
   * @see http://locutus.io/php/strings/number_format/
   *
   * @param {number|string} number Number to format.
   * @return {?string} A formatted string.
   */


  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(NumberFormat, [{
    key: "format",
    value: function format(number) {
      var toFormat = number;

      if ('number' !== typeof number) {
        toFormat = parseFloat(number);
      }

      if (isNaN(toFormat)) {
        toFormat = 0;
      }

      var _this$config = this.config,
          precision = _this$config.precision,
          decimalSeparator = _this$config.decimalSeparator,
          thousandSeparator = _this$config.thousandSeparator;
      return numberFormatter(toFormat, precision, decimalSeparator, thousandSeparator);
    }
    /**
     * Removes any non-number formatting applied to a string
     * and returns a true number.
     *
     * @since 3.0
     *
     * @param {*} number Number to unformat.
     * @return {number} 0 If number cannot be unformatted properly.
     */

  }, {
    key: "unformat",
    value: function unformat(number) {
      var _this$config2 = this.config,
          decimalSeparator = _this$config2.decimalSeparator,
          thousandSeparator = _this$config2.thousandSeparator;

      if ('string' !== typeof number) {
        number = String(number);
      }

      var unformatted = number // Remove thousand separator.
      .replace(thousandSeparator, '') // Replace decimal separator with a decimal.
      .replace(decimalSeparator, '.');
      var parsed = parseFloat(unformatted);
      return isNaN(parsed) ? 0 : parsed;
    }
    /**
     * Converts a value to a non-negative number.
     *
     * @since 3.0
     *
     * @param {*} number Number to unformat.
     * @return {number} A non-negative number.
     */

  }, {
    key: "absint",
    value: function absint(number) {
      var unformatted = this.unformat(number);

      if (unformatted >= 0) {
        return unformatted;
      }

      return unformatted * -1;
    }
  }]);

  return NumberFormat;
}();

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/classCallCheck.js":
/*!***************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/classCallCheck.js ***!
  \***************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _classCallCheck(instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
}

module.exports = _classCallCheck, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/createClass.js":
/*!************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/createClass.js ***!
  \************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _defineProperties(target, props) {
  for (var i = 0; i < props.length; i++) {
    var descriptor = props[i];
    descriptor.enumerable = descriptor.enumerable || false;
    descriptor.configurable = true;
    if ("value" in descriptor) descriptor.writable = true;
    Object.defineProperty(target, descriptor.key, descriptor);
  }
}

function _createClass(Constructor, protoProps, staticProps) {
  if (protoProps) _defineProperties(Constructor.prototype, protoProps);
  if (staticProps) _defineProperties(Constructor, staticProps);
  Object.defineProperty(Constructor, "prototype", {
    writable: false
  });
  return Constructor;
}

module.exports = _createClass, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/defineProperty.js":
/*!***************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/defineProperty.js ***!
  \***************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _defineProperty(obj, key, value) {
  if (key in obj) {
    Object.defineProperty(obj, key, {
      value: value,
      enumerable: true,
      configurable: true,
      writable: true
    });
  } else {
    obj[key] = value;
  }

  return obj;
}

module.exports = _defineProperty, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ }),

/***/ "./node_modules/locutus/php/strings/number_format.js":
/*!***********************************************************!*\
  !*** ./node_modules/locutus/php/strings/number_format.js ***!
  \***********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


module.exports = function number_format(number, decimals, decPoint, thousandsSep) {
  // eslint-disable-line camelcase
  //  discuss at: https://locutus.io/php/number_format/
  // original by: Jonas Raoni Soares Silva (https://www.jsfromhell.com)
  // improved by: Kevin van Zonneveld (https://kvz.io)
  // improved by: davook
  // improved by: Brett Zamir (https://brett-zamir.me)
  // improved by: Brett Zamir (https://brett-zamir.me)
  // improved by: Theriault (https://github.com/Theriault)
  // improved by: Kevin van Zonneveld (https://kvz.io)
  // bugfixed by: Michael White (https://getsprink.com)
  // bugfixed by: Benjamin Lupton
  // bugfixed by: Allan Jensen (https://www.winternet.no)
  // bugfixed by: Howard Yeend
  // bugfixed by: Diogo Resende
  // bugfixed by: Rival
  // bugfixed by: Brett Zamir (https://brett-zamir.me)
  //  revised by: Jonas Raoni Soares Silva (https://www.jsfromhell.com)
  //  revised by: Luke Smith (https://lucassmith.name)
  //    input by: Kheang Hok Chin (https://www.distantia.ca/)
  //    input by: Jay Klehr
  //    input by: Amir Habibi (https://www.residence-mixte.com/)
  //    input by: Amirouche
  //   example 1: number_format(1234.56)
  //   returns 1: '1,235'
  //   example 2: number_format(1234.56, 2, ',', ' ')
  //   returns 2: '1 234,56'
  //   example 3: number_format(1234.5678, 2, '.', '')
  //   returns 3: '1234.57'
  //   example 4: number_format(67, 2, ',', '.')
  //   returns 4: '67,00'
  //   example 5: number_format(1000)
  //   returns 5: '1,000'
  //   example 6: number_format(67.311, 2)
  //   returns 6: '67.31'
  //   example 7: number_format(1000.55, 1)
  //   returns 7: '1,000.6'
  //   example 8: number_format(67000, 5, ',', '.')
  //   returns 8: '67.000,00000'
  //   example 9: number_format(0.9, 0)
  //   returns 9: '1'
  //  example 10: number_format('1.20', 2)
  //  returns 10: '1.20'
  //  example 11: number_format('1.20', 4)
  //  returns 11: '1.2000'
  //  example 12: number_format('1.2000', 3)
  //  returns 12: '1.200'
  //  example 13: number_format('1 000,50', 2, '.', ' ')
  //  returns 13: '100 050.00'
  //  example 14: number_format(1e-8, 8, '.', '')
  //  returns 14: '0.00000001'

  number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
  var n = !isFinite(+number) ? 0 : +number;
  var prec = !isFinite(+decimals) ? 0 : Math.abs(decimals);
  var sep = typeof thousandsSep === 'undefined' ? ',' : thousandsSep;
  var dec = typeof decPoint === 'undefined' ? '.' : decPoint;
  var s = '';

  var toFixedFix = function toFixedFix(n, prec) {
    if (('' + n).indexOf('e') === -1) {
      return +(Math.round(n + 'e+' + prec) + 'e-' + prec);
    } else {
      var arr = ('' + n).split('e');
      var sig = '';
      if (+arr[1] + prec > 0) {
        sig = '+';
      }
      return (+(Math.round(+arr[0] + 'e' + sig + (+arr[1] + prec)) + 'e-' + prec)).toFixed(prec);
    }
  };

  // @todo: for IE parseFloat(0.55).toFixed(0) = 0;
  s = (prec ? toFixedFix(n, prec).toString() : '' + Math.round(n)).split('.');
  if (s[0].length > 3) {
    s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
  }
  if ((s[1] || '').length < prec) {
    s[1] = s[1] || '';
    s[1] += new Array(prec - s[1].length + 1).join('0');
  }

  return s.join(dec);
};
//# sourceMappingURL=number_format.js.map

/***/ }),

/***/ "jquery":
/*!*************************!*\
  !*** external "jQuery" ***!
  \*************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = jQuery;

/***/ })

/******/ });
//# sourceMappingURL=cs-admin-reports.js.map