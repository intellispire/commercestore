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
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/js/frontend/checkout/index.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/js/frontend/checkout/components/agree-to-terms/index.js":
/*!************************************************************************!*\
  !*** ./assets/js/frontend/checkout/components/agree-to-terms/index.js ***!
  \************************************************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function($) {/* harmony import */ var utils_jquery_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! utils/jquery.js */ "./assets/js/utils/jquery.js");
/* global $ */

/**
 * Internal dependencies.
 */

/**
 * DOM ready.
 *
 * @since 3.0
 */

Object(utils_jquery_js__WEBPACK_IMPORTED_MODULE_0__["jQueryReady"])(function () {
  /**
   * Toggles term content when clicked.
   *
   * @since unknown
   *
   * @param {Object} e Click event.
   */
  $(document.body).on('click', '.cs_terms_links', function (e) {
    e.preventDefault();
    var terms = $(this).parent();
    terms.prev('.cs-terms').slideToggle();
    terms.find('.cs_terms_links').toggle();
  });
});
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery")))

/***/ }),

/***/ "./assets/js/frontend/checkout/index.js":
/*!**********************************************!*\
  !*** ./assets/js/frontend/checkout/index.js ***!
  \**********************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _components_agree_to_terms__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./components/agree-to-terms */ "./assets/js/frontend/checkout/components/agree-to-terms/index.js");
/* harmony import */ var _utils_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./utils.js */ "./assets/js/frontend/checkout/utils.js");
/**
 * Internal dependencies.
 */

 // Backwards compatibility. Assign function to global namespace.

window.recalculate_taxes = _utils_js__WEBPACK_IMPORTED_MODULE_1__["recalculateTaxes"];

window.CS_Checkout = function ($) {
  'use strict';

  var $body, $form, $cs_cart_amount, before_discount, $checkout_form_wrap;

  function init() {
    $body = $(document.body);
    $form = $('#cs_purchase_form');
    $cs_cart_amount = $('.cs_cart_amount');
    before_discount = $cs_cart_amount.text();
    $checkout_form_wrap = $('#cs_checkout_form_wrap');
    $body.on('cs_gateway_loaded', function (e) {
      cs_format_card_number($form);
    });
    $body.on('keyup change', '.cs-do-validate .card-number', function () {
      cs_validate_card($(this));
    });
    $body.on('blur change', '.card-name', function () {
      var name_field = $(this);
      name_field.validateCreditCard(function (result) {
        if (result.card_type != null) {
          name_field.removeClass('valid').addClass('error');
          $('#cs-purchase-button').attr('disabled', 'disabled');
        } else {
          name_field.removeClass('error').addClass('valid');
          $('#cs-purchase-button').removeAttr('disabled');
        }
      });
    }); // Make sure a gateway is selected

    $body.on('submit', '#cs_payment_mode', function () {
      var gateway = $('#cs-gateway option:selected').val();

      if (gateway == 0) {
        alert(cs_global_vars.no_gateway);
        return false;
      }
    }); // Add a class to the currently selected gateway on click

    $body.on('click', '#cs_payment_mode_select input', function () {
      $('#cs_payment_mode_select label.cs-gateway-option-selected').removeClass('cs-gateway-option-selected');
      $('#cs_payment_mode_select input:checked').parent().addClass('cs-gateway-option-selected');
    }); // Validate and apply a discount

    $checkout_form_wrap.on('click', '.cs-apply-discount', apply_discount); // Prevent the checkout form from submitting when hitting Enter in the discount field

    $checkout_form_wrap.on('keypress', '#cs-discount', function (event) {
      if (event.keyCode == '13') {
        return false;
      }
    }); // Apply the discount when hitting Enter in the discount field instead

    $checkout_form_wrap.on('keyup', '#cs-discount', function (event) {
      if (event.keyCode == '13') {
        $checkout_form_wrap.find('.cs-apply-discount').trigger('click');
      }
    }); // Remove a discount

    $body.on('click', '.cs_discount_remove', remove_discount); // When discount link is clicked, hide the link, then show the discount input and set focus.

    $body.on('click', '.cs_discount_link', function (e) {
      e.preventDefault();
      $('.cs_discount_link').parent().hide();
      $('#cs-discount-code-wrap').show().find('#cs-discount').focus();
    }); // Hide / show discount fields for browsers without javascript enabled

    $body.find('#cs-discount-code-wrap').hide();
    $body.find('#cs_show_discount').show(); // Update the checkout when item quantities are updated

    $body.on('change', '.cs-item-quantity', update_item_quantities);
    $body.on('click', '.cs-amazon-logout #Logout', function (e) {
      e.preventDefault();
      amazon.Login.logout();
      window.location = cs_amazon.checkoutUri;
    });
  }

  function cs_validate_card(field) {
    var card_field = field;
    card_field.validateCreditCard(function (result) {
      var $card_type = $('.card-type');

      if (result.card_type == null) {
        $card_type.removeClass().addClass('off card-type');
        card_field.removeClass('valid');
        card_field.addClass('error');
      } else {
        $card_type.removeClass('off');
        $card_type.html(Object(_utils_js__WEBPACK_IMPORTED_MODULE_1__["getCreditCardIcon"])(result.card_type.name));
        $card_type.addClass(result.card_type.name);

        if (result.length_valid && result.luhn_valid) {
          card_field.addClass('valid');
          card_field.removeClass('error');
        } else {
          card_field.removeClass('valid');
          card_field.addClass('error');
        }
      }
    });
  }

  function cs_format_card_number(form) {
    var card_number = form.find('.card-number'),
        card_cvc = form.find('.card-cvc'),
        card_expiry = form.find('.card-expiry');

    if (card_number.length && 'function' === typeof card_number.payment) {
      card_number.payment('formatCardNumber');
      card_cvc.payment('formatCardCVC');
      card_expiry.payment('formatCardExpiry');
    }
  }

  function apply_discount(event) {
    event.preventDefault();
    var discount_code = $('#cs-discount').val(),
        cs_discount_loader = $('#cs-discount-loader'),
        required_inputs = $('#cs_cc_address .cs-input, #cs_cc_address .cs-select').filter('[required]');

    if (discount_code == '' || discount_code == cs_global_vars.enter_discount) {
      return false;
    }

    var postData = {
      action: 'cs_apply_discount',
      code: discount_code,
      form: $('#cs_purchase_form').serialize()
    };
    $('#cs-discount-error-wrap').html('').hide();
    cs_discount_loader.show();
    $.ajax({
      type: 'POST',
      data: postData,
      dataType: 'json',
      url: cs_global_vars.ajaxurl,
      xhrFields: {
        withCredentials: true
      },
      success: function success(discount_response) {
        if (discount_response) {
          if (discount_response.msg == 'valid') {
            $('.cs_cart_discount').html(discount_response.html);
            $('.cs_cart_discount_row').show();
            $('.cs_cart_amount').each(function () {
              // Format discounted amount for display.
              $(this).text(discount_response.total); // Set data attribute to new (unformatted) discounted amount.'

              $(this).data('total', discount_response.total_plain);
            });
            $('#cs-discount', $checkout_form_wrap).val('');
            Object(_utils_js__WEBPACK_IMPORTED_MODULE_1__["recalculateTaxes"])();

            if ('0.00' == discount_response.total_plain) {
              $('#cs_cc_fields,#cs_cc_address,#cs_payment_mode_select').slideUp();
              required_inputs.prop('required', false);
              $('input[name="cs-gateway"]').val('manual');
            } else {
              required_inputs.prop('required', true);
              $('#cs_cc_fields,#cs_cc_address').slideDown();
            }

            $body.trigger('cs_discount_applied', [discount_response]);
          } else {
            $('#cs-discount-error-wrap').html('<span class="cs_error">' + discount_response.msg + '</span>');
            $('#cs-discount-error-wrap').show();
            $body.trigger('cs_discount_invalid', [discount_response]);
          }
        } else {
          if (window.console && window.console.log) {
            console.log(discount_response);
          }

          $body.trigger('cs_discount_failed', [discount_response]);
        }

        cs_discount_loader.hide();
      }
    }).fail(function (data) {
      if (window.console && window.console.log) {
        console.log(data);
      }
    });
    return false;
  }

  function remove_discount(event) {
    var $this = $(this),
        postData = {
      action: 'cs_remove_discount',
      code: $this.data('code')
    };
    $.ajax({
      type: 'POST',
      data: postData,
      dataType: 'json',
      url: cs_global_vars.ajaxurl,
      xhrFields: {
        withCredentials: true
      },
      success: function success(discount_response) {
        var zero = '0' + cs_global_vars.decimal_separator + '00';
        $('.cs_cart_amount').each(function () {
          if (cs_global_vars.currency_sign + zero == $(this).text() || zero + cs_global_vars.currency_sign == $(this).text()) {
            // We're removing a 100% discount code so we need to force the payment gateway to reload
            window.location.reload();
          } // Format discounted amount for display.


          $(this).text(discount_response.total); // Set data attribute to new (unformatted) discounted amount.'

          $(this).data('total', discount_response.total_plain);
        });
        $('.cs_cart_discount').html(discount_response.html);

        if (discount_response.discounts && 0 === discount_response.discounts.length) {
          $('.cs_cart_discount_row').hide();
        }

        Object(_utils_js__WEBPACK_IMPORTED_MODULE_1__["recalculateTaxes"])();
        $('#cs_cc_fields,#cs_cc_address').slideDown();
        $body.trigger('cs_discount_removed', [discount_response]);
      }
    }).fail(function (data) {
      if (window.console && window.console.log) {
        console.log(data);
      }
    });
    return false;
  }

  function update_item_quantities(event) {
    var $this = $(this),
        quantity = $this.val(),
        key = $this.data('key'),
        download_id = $this.closest('.cs_cart_item').data('download-id'),
        options = $this.parent().find('input[name="cs-cart-download-' + key + '-options"]').val();
    var cs_cc_address = $('#cs_cc_address');
    var billing_country = cs_cc_address.find('#billing_country').val(),
        card_state = cs_cc_address.find('#card_state').val();
    var postData = {
      action: 'cs_update_quantity',
      quantity: quantity,
      download_id: download_id,
      options: options,
      billing_country: billing_country,
      card_state: card_state
    }; //cs_discount_loader.show();

    $.ajax({
      type: 'POST',
      data: postData,
      dataType: 'json',
      url: cs_global_vars.ajaxurl,
      xhrFields: {
        withCredentials: true
      },
      success: function success(response) {
        $('.cs_cart_subtotal_amount').each(function () {
          $(this).text(response.subtotal);
        });
        $('.cs_cart_tax_amount').each(function () {
          $(this).text(response.taxes);
        });
        $('.cs_cart_amount').each(function () {
          $(this).text(response.total);
          $body.trigger('cs_quantity_updated', [response]);
        });
      }
    }).fail(function (data) {
      if (window.console && window.console.log) {
        console.log(data);
      }
    });
    return false;
  } // Expose some functions or variables to window.CS_Checkout object


  return {
    init: init,
    recalculate_taxes: _utils_js__WEBPACK_IMPORTED_MODULE_1__["recalculateTaxes"]
  };
}(window.jQuery); // init on document.ready


window.jQuery(document).ready(CS_Checkout.init);

/***/ }),

/***/ "./assets/js/frontend/checkout/utils.js":
/*!**********************************************!*\
  !*** ./assets/js/frontend/checkout/utils.js ***!
  \**********************************************/
/*! exports provided: getCreditCardIcon, recalculateTaxes */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function(jQuery) {/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getCreditCardIcon", function() { return getCreditCardIcon; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "recalculateTaxes", function() { return recalculateTaxes; });
/* global cs_global_vars */

/**
 * Generate markup for a credit card icon based on a passed type.
 *
 * @param {string} type Credit card type.
 * @return HTML markup.
 */
var getCreditCardIcon = function getCreditCardIcon(type) {
  var width;
  var name = type;

  switch (type) {
    case 'amex':
      name = 'americanexpress';
      width = 32;
      break;

    default:
      width = 50;
      break;
  }

  return "\n    <svg\n      width=".concat(width, "\n      height=", 32, "\n      class=\"payment-icon icon-").concat(name, "\"\n      role=\"img\"\n    >\n      <use\n        href=\"#icon-").concat(name, "\"\n        xlink:href=\"#icon-").concat(name, "\">\n      </use>\n    </svg>");
};
var ajax_tax_count = 0;
/**
 * Recalulate taxes.
 *
 * @param {string} state State to calculate taxes for.
 * @return {Promise}
 */

function recalculateTaxes(state) {
  if ('1' != cs_global_vars.taxes_enabled) {
    return;
  } // Taxes not enabled


  var $cs_cc_address = jQuery('#cs_cc_address');
  var billing_country = $cs_cc_address.find('#billing_country').val(),
      card_address = $cs_cc_address.find('#card_address').val(),
      card_address_2 = $cs_cc_address.find('#card_address_2').val(),
      card_city = $cs_cc_address.find('#card_city').val(),
      card_state = $cs_cc_address.find('#card_state').val(),
      card_zip = $cs_cc_address.find('#card_zip').val();

  if (!state) {
    state = card_state;
  }

  var postData = {
    action: 'cs_recalculate_taxes',
    card_address: card_address,
    card_address_2: card_address_2,
    card_city: card_city,
    card_zip: card_zip,
    state: state,
    billing_country: billing_country,
    nonce: jQuery('#cs-checkout-address-fields-nonce').val()
  };
  jQuery('#cs_purchase_submit [type=submit]').after('<span class="cs-loading-ajax cs-recalculate-taxes-loading cs-loading"></span>');
  var current_ajax_count = ++ajax_tax_count;
  return jQuery.ajax({
    type: 'POST',
    data: postData,
    dataType: 'json',
    url: cs_global_vars.ajaxurl,
    xhrFields: {
      withCredentials: true
    },
    success: function success(tax_response) {
      // Only update tax info if this response is the most recent ajax call.
      // Avoids bug with form autocomplete firing multiple ajax calls at the same time and not
      // being able to predict the call response order.
      if (current_ajax_count === ajax_tax_count) {
        if (tax_response.html) {
          jQuery('#cs_checkout_cart_form').replaceWith(tax_response.html);
        }

        jQuery('.cs_cart_amount').html(tax_response.total);

        var _tax_data = new Object();

        _tax_data.postdata = postData;
        _tax_data.response = tax_response;
        jQuery('body').trigger('cs_taxes_recalculated', [_tax_data]);
      }

      jQuery('.cs-recalculate-taxes-loading').remove();
    }
  }).fail(function (data) {
    if (window.console && window.console.log) {
      console.log(data);

      if (current_ajax_count === ajax_tax_count) {
        jQuery('body').trigger('cs_taxes_recalculated', [tax_data]);
      }
    }
  });
}
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery")))

/***/ }),

/***/ "./assets/js/utils/jquery.js":
/*!***********************************!*\
  !*** ./assets/js/utils/jquery.js ***!
  \***********************************/
/*! exports provided: jQueryReady */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function(jQuery) {/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "jQueryReady", function() { return jQueryReady; });
/* global jQuery */

/**
 * Safe wrapper for jQuery DOM ready.
 *
 * This should be used only when a script requires the use of jQuery.
 *
 * @param {Function} callback Function to call when ready.
 */
var jQueryReady = function jQueryReady(callback) {
  (function ($) {
    $(callback);
  })(jQuery);
};
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
//# sourceMappingURL=cs-checkout-global.js.map