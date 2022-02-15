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
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/js/admin/settings/index.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/js/admin/settings/gateways/paypal.js":
/*!*****************************************************!*\
  !*** ./assets/js/admin/settings/gateways/paypal.js ***!
  \*****************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(jQuery) {jQuery(document).ready(function ($) {
  /**
   * Connect to PayPal
   */
  $('#cs-paypal-commerce-connect').on('click', function (e) {
    e.preventDefault(); // Clear errors.

    var errorContainer = $('#cs-paypal-commerce-errors');
    errorContainer.empty().removeClass('notice notice-error');
    var button = document.getElementById('cs-paypal-commerce-connect');
    button.classList.add('updating-message');
    button.disabled = true;
    $.post(ajaxurl, {
      action: 'cs_paypal_commerce_connect',
      _ajax_nonce: $(this).data('nonce')
    }, function (response) {
      if (!response.success) {
        console.log('Connection failure', response.data);
        button.classList.remove('updating-message');
        button.disabled = false; // Set errors.

        errorContainer.html('<p>' + response.data + '</p>').addClass('notice notice-error');
        return;
      }

      var paypalLinkEl = document.getElementById('cs-paypal-commerce-link');
      paypalLinkEl.href = response.data.signupLink + '&displayMode=minibrowser';
      paypalLinkEl.click();
    });
  });
  /**
   * Checks the PayPal connection & webhook status.
   */

  function csPayPalGetAccountStatus() {
    var accountInfoEl = document.getElementById('cs-paypal-commerce-connect-wrap');

    if (accountInfoEl) {
      $.post(ajaxurl, {
        action: 'cs_paypal_commerce_get_account_info',
        _ajax_nonce: accountInfoEl.getAttribute('data-nonce')
      }, function (response) {
        var newHtml = '<p>' + csPayPalConnectVars.defaultError + '</p>';

        if (response.success) {
          newHtml = response.data.account_status;

          if (response.data.actions && response.data.actions.length) {
            newHtml += '<p class="cs-paypal-connect-actions">' + response.data.actions.join(' ') + '</p>';
          }
        } else if (response.data && response.data.message) {
          newHtml = response.data.message;
        }

        accountInfoEl.innerHTML = newHtml; // Remove old status messages.

        accountInfoEl.classList.remove('notice-success', 'notice-warning', 'notice-error'); // Add new one.

        var newClass = response.success && response.data.status ? 'notice-' + response.data.status : 'notice-error';
        accountInfoEl.classList.add(newClass);
      });
    }
  }

  csPayPalGetAccountStatus();
  /**
   * Create webhook
   */

  $(document).on('click', '.cs-paypal-connect-action', function (e) {
    e.preventDefault();
    var button = $(this);
    button.prop('disabled', true);
    button.addClass('updating-message');
    var errorWrap = $('#cs-paypal-commerce-connect-wrap').find('.cs-paypal-actions-error-wrap');

    if (errorWrap.length) {
      errorWrap.remove();
    }

    $.post(ajaxurl, {
      action: button.data('action'),
      _ajax_nonce: button.data('nonce')
    }, function (response) {
      button.prop('disabled', false);
      button.removeClass('updating-message');

      if (response.success) {
        button.addClass('updated-message'); // Refresh account status.

        csPayPalGetAccountStatus();
      } else {
        button.parent().after('<p class="cs-paypal-actions-error-wrap">' + response.data + '</p>');
      }
    });
  });
});

window.csPayPalOnboardingCallback = function csPayPalOnboardingCallback(authCode, shareId) {
  var connectButton = document.getElementById('cs-paypal-commerce-connect');
  var errorContainer = document.getElementById('cs-paypal-commerce-errors');
  jQuery.post(ajaxurl, {
    action: 'cs_paypal_commerce_get_access_token',
    auth_code: authCode,
    share_id: shareId,
    _ajax_nonce: connectButton.getAttribute('data-nonce')
  }, function (response) {
    connectButton.classList.remove('updating-message');

    if (!response.success) {
      connectButton.disabled = false;
      errorContainer.innerHTML = '<p>' + response.data + '</p>';
      errorContainer.classList.add('notice notice-error');
      return;
    }

    connectButton.classList.add('updated-message');
    window.location.reload();
  });
};
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery")))

/***/ }),

/***/ "./assets/js/admin/settings/index.js":
/*!*******************************************!*\
  !*** ./assets/js/admin/settings/index.js ***!
  \*******************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function($, jQuery) {/* harmony import */ var _recapture__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./recapture */ "./assets/js/admin/settings/recapture/index.js");
/* harmony import */ var _gateways_paypal__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./gateways/paypal */ "./assets/js/admin/settings/gateways/paypal.js");
/* harmony import */ var _gateways_paypal__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_gateways_paypal__WEBPACK_IMPORTED_MODULE_1__);


/**
 * Settings screen JS
 */

var CS_Settings = {
  init: function init() {
    this.general();
    this.misc();
    this.gateways();
    this.emails();
  },
  general: function general() {
    var cs_color_picker = $('.cs-color-picker');

    if (cs_color_picker.length) {
      cs_color_picker.wpColorPicker();
    } // Settings Upload field JS


    if (typeof wp === 'undefined' || '1' !== cs_vars.new_media_ui) {
      // Old Thickbox uploader
      var cs_settings_upload_button = $('.cs_settings_upload_button');

      if (cs_settings_upload_button.length > 0) {
        window.formfield = '';
        $(document.body).on('click', cs_settings_upload_button, function (e) {
          e.preventDefault();
          window.formfield = $(this).parent().prev();
          window.tbframe_interval = setInterval(function () {
            jQuery('#TB_iframeContent').contents().find('.savesend .button').val(cs_vars.use_this_file).end().find('#insert-gallery, .wp-post-thumbnail').hide();
          }, 2000);
          tb_show(cs_vars.add_new_download, 'media-upload.php?TB_iframe=true');
        });
        window.cs_send_to_editor = window.send_to_editor;

        window.send_to_editor = function (html) {
          if (window.formfield) {
            imgurl = $('a', '<div>' + html + '</div>').attr('href');
            window.formfield.val(imgurl);
            window.clearInterval(window.tbframe_interval);
            tb_remove();
          } else {
            window.cs_send_to_editor(html);
          }

          window.send_to_editor = window.cs_send_to_editor;
          window.formfield = '';
          window.imagefield = false;
        };
      }
    } else {
      // WP 3.5+ uploader
      var file_frame;
      window.formfield = '';
      $(document.body).on('click', '.cs_settings_upload_button', function (e) {
        e.preventDefault();
        var button = $(this);
        window.formfield = $(this).parent().prev(); // If the media frame already exists, reopen it.

        if (file_frame) {
          //file_frame.uploader.uploader.param( 'post_id', set_to_post_id );
          file_frame.open();
          return;
        } // Create the media frame.


        file_frame = wp.media.frames.file_frame = wp.media({
          title: button.data('uploader_title'),
          library: {
            type: 'image'
          },
          button: {
            text: button.data('uploader_button_text')
          },
          multiple: false
        });
        file_frame.on('menu:render:default', function (view) {
          // Store our views in an object.
          var views = {}; // Unset default menu items

          view.unset('library-separator');
          view.unset('gallery');
          view.unset('featured-image');
          view.unset('embed'); // Initialize the views in our view object.

          view.set(views);
        }); // When an image is selected, run a callback.

        file_frame.on('select', function () {
          var selection = file_frame.state().get('selection');
          selection.each(function (attachment, index) {
            attachment = attachment.toJSON();
            window.formfield.val(attachment.url);
          });
        }); // Finally, open the modal

        file_frame.open();
      }); // WP 3.5+ uploader

      var file_frame;
      window.formfield = '';
    }
  },
  misc: function misc() {
    var downloadMethod = $('select[name="cs_settings[download_method]"]'),
        symlink = downloadMethod.parent().parent().next(); // Hide Symlink option if Download Method is set to Direct

    if (downloadMethod.val() === 'direct') {
      symlink.css('opacity', '0.4');
      symlink.find('input').prop('checked', false).prop('disabled', true);
    } // Toggle download method option


    downloadMethod.on('change', function () {
      if ($(this).val() === 'direct') {
        symlink.css('opacity', '0.4');
        symlink.find('input').prop('checked', false).prop('disabled', true);
      } else {
        symlink.find('input').prop('disabled', false);
        symlink.css('opacity', '1');
      }
    });
  },
  gateways: function gateways() {
    $('#cs-payment-gateways input[type="checkbox"]').on('change', function () {
      var gateway = $(this),
          gateway_key = gateway.data('gateway-key'),
          default_gateway = $('#cs_settings\\[default_gateway\\]'),
          option = default_gateway.find('option[value="' + gateway_key + '"]'); // Toggle enable/disable based

      option.prop('disabled', function (i, v) {
        return !v;
      }); // Maybe deselect

      if (option.prop('selected')) {
        option.prop('selected', false);
      }

      default_gateway.trigger('chosen:updated');
    });
  },
  emails: function emails() {
    $('#cs-recapture-connect').on('click', function (e) {
      e.preventDefault();
      $(this).html(cs_vars.wait + ' <span class="cs-loading"></span>');
      document.body.style.cursor = 'wait';
      Object(_recapture__WEBPACK_IMPORTED_MODULE_0__["recaptureRemoteInstall"])();
    });
  }
};
jQuery(document).ready(function ($) {
  CS_Settings.init();
});
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery"), __webpack_require__(/*! jquery */ "jquery")))

/***/ }),

/***/ "./assets/js/admin/settings/recapture/index.js":
/*!*****************************************************!*\
  !*** ./assets/js/admin/settings/recapture/index.js ***!
  \*****************************************************/
/*! exports provided: recaptureRemoteInstall */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function(jQuery) {/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "recaptureRemoteInstall", function() { return recaptureRemoteInstall; });
var recaptureRemoteInstall = function recaptureRemoteInstall() {
  var data = {
    'action': 'cs_recapture_remote_install'
  };
  jQuery.post(ajaxurl, data, function (response) {
    if (!response.success) {
      if (confirm(response.data.error)) {
        location.reload();
        return;
      }
    }

    window.location.href = 'https://recapture.io/register';
  });
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
//# sourceMappingURL=cs-admin-settings.js.map