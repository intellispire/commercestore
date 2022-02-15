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
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/js/admin/settings/extension-manager/index.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/js/admin/settings/extension-manager/index.js":
/*!*************************************************************!*\
  !*** ./assets/js/admin/settings/extension-manager/index.js ***!
  \*************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(jQuery) {/* global CSExtensionManager, ajaxurl */
;

(function (document, $) {
  'use strict';

  $('.cs-extension-manager__action').on('click', function (e) {
    e.preventDefault();
    var $btn = $(this),
        action = $btn.attr('data-action'),
        plugin = $btn.attr('data-plugin'),
        type = $btn.attr('data-type'),
        ajaxAction = '';

    if ($btn.attr('disabled')) {
      return;
    }

    switch (action) {
      case 'activate':
        ajaxAction = 'cs_activate_extension';
        $btn.text(CSExtensionManager.activating);
        break;

      case 'install':
        ajaxAction = 'cs_install_extension';
        $btn.text(CSExtensionManager.installing);
        break;

      default:
        return;
    }

    $btn.removeClass('button-primary').attr('disabled', true).addClass('updating-message');
    var data = {
      action: ajaxAction,
      nonce: CSExtensionManager.extension_manager_nonce,
      plugin: plugin,
      type: type,
      pass: $btn.attr('data-pass'),
      id: $btn.attr('data-id'),
      product: $btn.attr('data-product')
    };
    $.post(ajaxurl, data).done(function (res) {
      console.log(res);
      var thisStep = $btn.closest('.cs-extension-manager__step');

      if (res.success) {
        var nextStep = thisStep.next();

        if (nextStep.length) {
          thisStep.fadeOut();
          nextStep.prepend('<div class="notice inline-notice notice-success"><p>' + res.data.message + '</p></div>');
          nextStep.fadeIn();
        }
      } else {
        thisStep.fadeOut();
        var message = res.data.message;
        /**
         * The install class returns an array of error messages, and res.data.message will be undefined.
         * In that case, we'll use the standard failure messages.
         */

        if (!message) {
          if ('plugin' !== type) {
            message = CSExtensionManager.extension_install_failed;
          } else {
            message = CSExtensionManager.plugin_install_failed;
          }
        }

        thisStep.after('<div class="notice inline-notice notice-warning"><p>' + message + '</p></div>');
      }
    });
  });
})(document, jQuery);
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery")))

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
//# sourceMappingURL=cs-admin-extension-manager.js.map