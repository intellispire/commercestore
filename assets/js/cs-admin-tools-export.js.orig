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
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/js/admin/tools/export/index.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/js/admin/tools/export/index.js":
/*!***********************************************!*\
  !*** ./assets/js/admin/tools/export/index.js ***!
  \***********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function($, jQuery) {/**
 * Export screen JS
 */
var CS_Export = {
  init: function init() {
    this.submit();
  },
  submit: function submit() {
    var self = this;
    $(document.body).on('submit', '.cs-export-form', function (e) {
      e.preventDefault();
      var form = $(this),
          submitButton = form.find('button[type="submit"]').first();

      if (submitButton.hasClass('button-disabled') || submitButton.is(':disabled')) {
        return;
      }

      var data = form.serialize();

      if (submitButton.hasClass('button-primary')) {
        submitButton.removeClass('button-primary').addClass('button-secondary');
      }

      submitButton.attr('disabled', true).addClass('updating-message');
      form.find('.notice-wrap').remove();
      form.append('<div class="notice-wrap"><div class="cs-progress"><div></div></div></div>'); // start the process

      self.process_step(1, data, self);
    });
  },
  process_step: function process_step(step, data, self) {
    $.ajax({
      type: 'POST',
      url: ajaxurl,
      data: {
        form: data,
        action: 'cs_do_ajax_export',
        step: step
      },
      dataType: 'json',
      success: function success(response) {
        if ('done' === response.step || response.error || response.success) {
          // We need to get the actual in progress form, not all forms on the page
          var export_form = $('.cs-export-form').find('.cs-progress').parent().parent();
          var notice_wrap = export_form.find('.notice-wrap');
          export_form.find('button').attr('disabled', false).removeClass('updating-message').addClass('updated-message');
          export_form.find('button .spinner').hide().css('visibility', 'visible');

          if (response.error) {
            var error_message = response.message;
            notice_wrap.html('<div class="updated error"><p>' + error_message + '</p></div>');
          } else if (response.success) {
            var success_message = response.message;
            notice_wrap.html('<div id="cs-batch-success" class="updated notice"><p>' + success_message + '</p></div>');

            if (response.data) {
              $.each(response.data, function (key, value) {
                $('.cs_' + key).html(value);
              });
            }
          } else {
            notice_wrap.remove();
            window.location = response.url;
          }
        } else {
          $('.cs-progress div').animate({
            width: response.percentage + '%'
          }, 50, function () {// Animation complete.
          });
          self.process_step(parseInt(response.step), data, self);
        }
      }
    }).fail(function (response) {
      if (window.console && window.console.log) {
        console.log(response);
      }
    });
  }
};
jQuery(document).ready(function ($) {
  CS_Export.init();
});
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery"), __webpack_require__(/*! jquery */ "jquery")))

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
//# sourceMappingURL=cs-admin-tools-export.js.map