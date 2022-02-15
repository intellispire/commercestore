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
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/js/frontend/gateways/paypal.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/js/frontend/gateways/paypal.js":
/*!***********************************************!*\
  !*** ./assets/js/frontend/gateways/paypal.js ***!
  \***********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(jQuery) {/* global csPayPalVars, cs_global_vars */
var CS_PayPal = {
  isMounted: false,

  /**
   * Initializes PayPal buttons and sets up some events.
   */
  init: function init() {
    if (document.getElementById('cs-paypal-container')) {
      this.initButtons('#cs-paypal-container', 'checkout');
    }

    jQuery(document.body).on('cs_discount_applied', this.maybeRefreshPage);
    jQuery(document.body).on('cs_discount_removed', this.maybeRefreshPage);
  },

  /**
   * Determines whether or not the selected gateway is PayPal.
   * @returns {boolean}
   */
  isPayPal: function isPayPal() {
    var chosenGateway = false;

    if (jQuery('select#cs-gateway, input.cs-gateway').length) {
      chosenGateway = jQuery("meta[name='cs-chosen-gateway']").attr('content');
    }

    if (!chosenGateway && cs_scripts.default_gateway) {
      chosenGateway = cs_scripts.default_gateway;
    }

    return 'paypal_commerce' === chosenGateway;
  },

  /**
   * Refreshes the page when adding or removing a 100% discount.
   *
   * @param e
   * @param {object} data
   */
  maybeRefreshPage: function maybeRefreshPage(e, data) {
    if (0 === data.total_plain && CS_PayPal.isPayPal()) {
      window.location.reload();
    } else if (!CS_PayPal.isMounted && CS_PayPal.isPayPal() && data.total_plain > 0) {
      window.location.reload();
    }
  },

  /**
   * Sets the error HTML, depending on the context.
   *
   * @param {string|HTMLElement} container
   * @param {string} context
   * @param {string} errorHtml
   */
  setErrorHtml: function setErrorHtml(container, context, errorHtml) {
    // Format errors.
    if ('checkout' === context && 'undefined' !== typeof cs_global_vars && cs_global_vars.checkout_error_anchor) {
      // Checkout errors.
      var errorWrapper = document.getElementById('cs-paypal-errors-wrap');

      if (errorWrapper) {
        errorWrapper.innerHTML = errorHtml;
      }
    } else if ('buy_now' === context) {
      // Buy Now errors
      var form = container.closest('.cs_download_purchase_form');
      var errorWrapper = form ? form.querySelector('.cs-paypal-checkout-buy-now-error-wrapper') : false;

      if (errorWrapper) {
        errorWrapper.innerHTML = errorHtml;
      }
    }

    jQuery(document.body).trigger('cs_checkout_error', [errorHtml]);
  },

  /**
   * Initializes PayPal buttons
   *
   * @param {string|HTMLElement} container Element to render the buttons in.
   * @param {string} context   Context for the button. Either `checkout` or `buy_now`.
   */
  initButtons: function initButtons(container, context) {
    CS_PayPal.isMounted = true;
    paypal.Buttons(CS_PayPal.getButtonArgs(container, context)).render(container);
    document.dispatchEvent(new CustomEvent('cs_paypal_buttons_mounted'));
  },

  /**
   * Retrieves the arguments used to build the PayPal button.
   *
   * @param {string|HTMLElement} container Element to render the buttons in.
   * @param {string} context   Context for the button. Either `checkout` or `buy_now`.
   */
  getButtonArgs: function getButtonArgs(container, context) {
    var form = 'checkout' === context ? document.getElementById('cs_purchase_form') : container.closest('.cs_download_purchase_form');
    var errorWrapper = 'checkout' === context ? form.querySelector('#cs-paypal-errors-wrap') : form.querySelector('.cs-paypal-checkout-buy-now-error-wrapper');
    var spinner = 'checkout' === context ? document.getElementById('cs-paypal-spinner') : form.querySelector('.cs-paypal-spinner');
    var nonceEl = form.querySelector('input[name="cs_process_paypal_nonce"]');
    var tokenEl = form.querySelector('input[name="cs-process-paypal-token"]');
    var createFunc = 'subscription' === csPayPalVars.intent ? 'createSubscription' : 'createOrder';
    var buttonArgs = {
      onApprove: function onApprove(data, actions) {
        var formData = new FormData();
        formData.append('action', csPayPalVars.approvalAction);
        formData.append('cs_process_paypal_nonce', nonceEl.value);
        formData.append('token', tokenEl.getAttribute('data-token'));
        formData.append('timestamp', tokenEl.getAttribute('data-timestamp'));

        if (data.orderID) {
          formData.append('paypal_order_id', data.orderID);
        }

        if (data.subscriptionID) {
          formData.append('paypal_subscription_id', data.subscriptionID);
        }

        return fetch(cs_scripts.ajaxurl, {
          method: 'POST',
          body: formData
        }).then(function (response) {
          return response.json();
        }).then(function (responseData) {
          if (responseData.success && responseData.data.redirect_url) {
            window.location = responseData.data.redirect_url;
          } else {
            // Hide spinner.
            spinner.style.display = 'none';
            var errorHtml = responseData.data.message ? responseData.data.message : csPayPalVars.defaultError;
            CS_PayPal.setErrorHtml(container, context, errorHtml); // @link https://developer.paypal.com/docs/checkout/integration-features/funding-failure/

            if (responseData.data.retry) {
              return actions.restart();
            }
          }
        });
      },
      onError: function onError(error) {
        // Hide spinner.
        spinner.style.display = 'none';
        error.name = '';
        CS_PayPal.setErrorHtml(container, context, error);
      },
      onCancel: function onCancel(data) {
        // Hide spinner.
        spinner.style.display = 'none';
        var formData = new FormData();
        formData.append('action', 'cs_cancel_paypal_order');
        return fetch(cs_scripts.ajaxurl, {
          method: 'POST',
          body: formData
        }).then(function (response) {
          return response.json();
        }).then(function (responseData) {
          if (responseData.success) {
            var nonces = responseData.data.nonces;
            Object.keys(nonces).forEach(function (key) {
              document.getElementById('cs-gateway-' + key).setAttribute('data-' + key + '-nonce', nonces[key]);
            });
          }
        });
      }
    };
    /*
     * Add style if we have any
     *
     * @link https://developer.paypal.com/docs/checkout/integration-features/customize-button/
     */

    if (csPayPalVars.style) {
      buttonArgs.style = csPayPalVars.style;
    }
    /*
     * Add the `create` logic. This gets added to `createOrder` for one-time purchases
     * or `createSubscription` for recurring.
     */


    buttonArgs[createFunc] = function (data, actions) {
      // Show spinner.
      spinner.style.display = 'block'; // Clear errors at the start of each attempt.

      if (errorWrapper) {
        errorWrapper.innerHTML = '';
      } // Submit the form via AJAX.


      return fetch(cs_scripts.ajaxurl, {
        method: 'POST',
        body: new FormData(form)
      }).then(function (response) {
        return response.json();
      }).then(function (orderData) {
        if (orderData.data && orderData.data.paypal_order_id) {
          // Add the nonce to the form so we can validate it later.
          if (orderData.data.nonce) {
            nonceEl.value = orderData.data.nonce;
          } // Add the token to the form so we can validate it later.


          if (orderData.data.token) {
            jQuery(tokenEl).attr('data-token', orderData.data.token);
            jQuery(tokenEl).attr('data-timestamp', orderData.data.timestamp);
          }

          return orderData.data.paypal_order_id;
        } else {
          // Error message.
          var errorHtml = csPayPalVars.defaultError;

          if (orderData.data && 'string' === typeof orderData.data) {
            errorHtml = orderData.data;
          } else if ('string' === typeof orderData) {
            errorHtml = orderData;
          }

          return new Promise(function (resolve, reject) {
            reject(errorHtml);
          });
        }
      });
    };

    return buttonArgs;
  }
};
/**
 * Initialize on checkout.
 */

jQuery(document.body).on('cs_gateway_loaded', function (e, gateway) {
  if ('paypal_commerce' !== gateway) {
    return;
  }

  CS_PayPal.init();
});
/**
 * Initialize Buy Now buttons.
 */

jQuery(document).ready(function ($) {
  var buyButtons = document.querySelectorAll('.cs-paypal-checkout-buy-now');

  for (var i = 0; i < buyButtons.length; i++) {
    var element = buyButtons[i]; // Skip if "Free Downloads" is enabled for this download.

    if (element.classList.contains('cs-free-download')) {
      continue;
    }

    var wrapper = element.closest('.cs_purchase_submit_wrapper');

    if (!wrapper) {
      continue;
    } // Clear contents of the wrapper.


    wrapper.innerHTML = ''; // Add error container after the wrapper.

    var errorNode = document.createElement('div');
    errorNode.classList.add('cs-paypal-checkout-buy-now-error-wrapper');
    wrapper.before(errorNode); // Add spinner container.

    var spinnerWrap = document.createElement('span');
    spinnerWrap.classList.add('cs-paypal-spinner', 'cs-loading-ajax', 'cs-loading');
    spinnerWrap.style.display = 'none';
    wrapper.after(spinnerWrap); // Initialize button.

    CS_PayPal.initButtons(wrapper, 'buy_now');
  }
});
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
//# sourceMappingURL=paypal-checkout.js.map