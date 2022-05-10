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
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/js/admin/index.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/js/admin/components/advanced-filters/index.js":
/*!**************************************************************!*\
  !*** ./assets/js/admin/components/advanced-filters/index.js ***!
  \**************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(jQuery) {/* global jQuery */
jQuery(document).ready(function ($) {
  $('.cs-advanced-filters-button').on('click', function (e) {
    e.preventDefault();
    $(this).closest('#cs-advanced-filters').toggleClass('open');
  });
});
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery")))

/***/ }),

/***/ "./assets/js/admin/components/chosen/index.js":
/*!****************************************************!*\
  !*** ./assets/js/admin/components/chosen/index.js ***!
  \****************************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function(jQuery) {/* harmony import */ var utils_chosen_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! utils/chosen.js */ "./assets/js/utils/chosen.js");
/* global _ */

/**
 * Internal dependencies.
 */

jQuery(document).ready(function ($) {
  // Globally apply to elements on the page.
  $('.cs-select-chosen').each(function () {
    var el = $(this);
    el.chosen(Object(utils_chosen_js__WEBPACK_IMPORTED_MODULE_0__["getChosenVars"])(el));
  });
  $('.cs-select-chosen .chosen-search input').each(function () {
    // Bail if placeholder already set
    if ($(this).attr('placeholder')) {
      return;
    }

    var selectElem = $(this).parent().parent().parent().prev('select.cs-select-chosen'),
        placeholder = selectElem.data('search-placeholder');

    if (placeholder) {
      $(this).attr('placeholder', placeholder);
    }
  }); // Add placeholders for Chosen input fields

  $('.chosen-choices').on('click', function () {
    var placeholder = $(this).parent().prev().data('search-placeholder');

    if (typeof placeholder === 'undefined') {
      placeholder = cs_vars.type_to_search;
    }

    $(this).children('li').children('input').attr('placeholder', placeholder);
  }); // This fixes the Chosen box being 0px wide when the thickbox is opened

  $('#post').on('click', '.cs-thickbox', function () {
    $('.cs-select-chosen', '#choose-download').css('width', '100%');
  }); // Variables for setting up the typing timer
  // Time in ms, Slow - 521ms, Moderate - 342ms, Fast - 300ms

  var userInteractionInterval = 342,
      typingTimerElements = '.cs-select-chosen .chosen-search input, .cs-select-chosen .search-field input',
      typingTimer; // Replace options with search results

  $(document.body).on('keyup', typingTimerElements, _.debounce(function (e) {
    var element = $(this),
        val = element.val(),
        container = element.closest('.cs-select-chosen'),
        select = container.prev(),
        select_type = select.data('search-type'),
        no_bundles = container.hasClass('no-bundles'),
        variations = container.hasClass('variations'),
        variations_only = container.hasClass('variations-only'),
        lastKey = e.which,
        search_type = 'cs_download_search'; // String replace the chosen container IDs

    container.attr('id').replace('_chosen', ''); // Detect if we have a defined search type, otherwise default to downloads

    if (typeof select_type !== 'undefined') {
      // Don't trigger AJAX if this select has all options loaded
      if ('no_ajax' === select_type) {
        return;
      }

      search_type = 'cs_' + select_type + '_search';
    } else {
      return;
    } // Don't fire if short or is a modifier key (shift, ctrl, apple command key, or arrow keys)


    if (val.length <= 3 && 'cs_download_search' === search_type || lastKey === 16 || lastKey === 13 || lastKey === 91 || lastKey === 17 || lastKey === 37 || lastKey === 38 || lastKey === 39 || lastKey === 40) {
      container.children('.spinner').remove();
      return;
    } // Maybe append a spinner


    if (!container.children('.spinner').length) {
      container.append('<span class="spinner is-active"></span>');
    }

    $.ajax({
      type: 'GET',
      dataType: 'json',
      url: ajaxurl,
      data: {
        s: val,
        action: search_type,
        no_bundles: no_bundles,
        variations: variations,
        variations_only: variations_only
      },
      beforeSend: function beforeSend() {
        select.closest('ul.chosen-results').empty();
      },
      success: function success(data) {
        // Remove all options but those that are selected
        $('option:not(:selected)', select).remove(); // Add any option that doesn't already exist

        $.each(data, function (key, item) {
          if (!$('option[value="' + item.id + '"]', select).length) {
            select.prepend('<option value="' + item.id + '">' + item.name + '</option>');
          }
        }); // Get the text immediately before triggering an update.
        // Any sooner will cause the text to jump around.

        var val = element.val(); // Update the options

        select.trigger('chosen:updated');
        element.val(val);
      }
    }).fail(function (response) {
      if (window.console && window.console.log) {
        console.log(response);
      }
    }).done(function (response) {
      container.children('.spinner').remove();
    });
  }, userInteractionInterval));
});
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery")))

/***/ }),

/***/ "./assets/js/admin/components/date-picker/index.js":
/*!*********************************************************!*\
  !*** ./assets/js/admin/components/date-picker/index.js ***!
  \*********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(jQuery) {/**
 * Date picker
 *
 * This juggles a few CSS classes to avoid styling collisions with other
 * third-party plugins.
 */
jQuery(document).ready(function ($) {
  var cs_datepicker = $('input.cs_datepicker');

  if (cs_datepicker.length > 0) {
    cs_datepicker // Disable autocomplete to avoid it covering the calendar
    .attr('autocomplete', 'off') // Invoke the datepickers
    .datepicker({
      dateFormat: cs_vars.date_picker_format,
      beforeShow: function beforeShow() {
        $('#ui-datepicker-div').removeClass('ui-datepicker').addClass('cs-datepicker');
      }
    });
  }
});
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery")))

/***/ }),

/***/ "./assets/js/admin/components/location/index.js":
/*!******************************************************!*\
  !*** ./assets/js/admin/components/location/index.js ***!
  \******************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(jQuery) {jQuery(document).ready(function ($) {
  $('.cs_countries_filter').on('change', function () {
    var select = $(this),
        data = {
      action: 'cs_get_shop_states',
      country: select.val(),
      nonce: select.data('nonce'),
      field_name: 'cs_regions_filter'
    };
    $.post(ajaxurl, data, function (response) {
      $('select.cs_regions_filter').find('option:gt(0)').remove();

      if ('nostates' !== response) {
        $(response).find('option:gt(0)').appendTo('select.cs_regions_filter');
      }

      $('select.cs_regions_filter').trigger('chosen:updated');
    });
    return false;
  });
});
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery")))

/***/ }),

/***/ "./assets/js/admin/components/notifications/index.js":
/*!***********************************************************!*\
  !*** ./assets/js/admin/components/notifications/index.js ***!
  \***********************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/* global cs_vars */
document.addEventListener('alpine:init', function () {
  Alpine.store('csNotifications', {
    isPanelOpen: false,
    notificationsLoaded: false,
    numberActiveNotifications: 0,
    activeNotifications: [],
    inactiveNotifications: [],
    init: function init() {
      var csNotifications = this;
      /*
       * The bubble starts out hidden until AlpineJS is initialized. Once it is, we remove
       * the hidden class. This prevents a flash of the bubble's visibility in the event that there
       * are no notifications.
       */

      var notificationCountBubble = document.querySelector('#cs-notification-button .cs-number');

      if (notificationCountBubble) {
        notificationCountBubble.classList.remove('cs-hidden');
      }

      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
          csNotifications.closePanel();
        }
      });
    },
    openPanel: function openPanel() {
      var _this = this;

      var panelHeader = document.getElementById('cs-notifications-header');

      if (this.notificationsLoaded) {
        this.isPanelOpen = true;

        if (panelHeader) {
          setTimeout(function () {
            panelHeader.focus();
          });
        }

        return;
      }

      this.isPanelOpen = true;
      this.apiRequest('/notifications', 'GET').then(function (data) {
        _this.activeNotifications = data.active;
        _this.inactiveNotifications = data.dismissed;
        _this.notificationsLoaded = true;

        if (panelHeader) {
          panelHeader.focus();
        }
      }).catch(function (error) {
        console.log('Notification error', error);
      });
    },
    closePanel: function closePanel() {
      if (!this.isPanelOpen) {
        return;
      }

      this.isPanelOpen = false;
      var notificationButton = document.getElementById('cs-notification-button');

      if (notificationButton) {
        notificationButton.focus();
      }
    },
    apiRequest: function apiRequest(endpoint, method) {
      return fetch(cs_vars.restBase + endpoint, {
        method: method,
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': cs_vars.restNonce
        }
      }).then(function (response) {
        if (!response.ok) {
          return Promise.reject(response);
        }
        /*
         * Returning response.text() instead of response.json() because dismissing
         * a notification doesn't return a JSON response, so response.json() will break.
         */


        return response.text(); //return response.json();
      }).then(function (data) {
        return data ? JSON.parse(data) : null;
      });
    },
    dismiss: function dismiss(event, index) {
      var _this2 = this;

      if ('undefined' === typeof this.activeNotifications[index]) {
        return;
      }

      event.target.disabled = true;
      var notification = this.activeNotifications[index];
      this.apiRequest('/notifications/' + notification.id, 'DELETE').then(function (response) {
        _this2.activeNotifications.splice(index, 1);

        _this2.numberActiveNotifications = _this2.activeNotifications.length;
      }).catch(function (error) {
        console.log('Dismiss error', error);
      });
    }
  });
});

/***/ }),

/***/ "./assets/js/admin/components/promos/index.js":
/*!****************************************************!*\
  !*** ./assets/js/admin/components/promos/index.js ***!
  \****************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(jQuery) {/* global ajaxurl */
jQuery(document).ready(function ($) {
  /**
   * Display notices
   */
  var topOfPageNotice = $('.cs-admin-notice-top-of-page');

  if (topOfPageNotice) {
    var topOfPageNoticeEl = topOfPageNotice.detach();
    $('#wpbody-content').prepend(topOfPageNoticeEl);
    topOfPageNotice.delay(1000).slideDown();
  }
  /**
   * Dismiss notices
   */


  $('.cs-promo-notice').each(function () {
    var notice = $(this);
    notice.on('click', '.cs-promo-notice-dismiss', function (e) {
      // Only prevent default behavior for buttons, not links.
      if (!$(this).attr('href')) {
        e.preventDefault();
      }

      $.ajax({
        type: 'POST',
        data: {
          action: 'cs_dismiss_promo_notice',
          notice_id: notice.data('id'),
          nonce: notice.data('nonce'),
          lifespan: notice.data('lifespan')
        },
        url: ajaxurl,
        success: function success(response) {
          notice.slideUp();
        }
      });
    });
  });
});
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery")))

/***/ }),

/***/ "./assets/js/admin/components/sortable-list/index.js":
/*!***********************************************************!*\
  !*** ./assets/js/admin/components/sortable-list/index.js ***!
  \***********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(jQuery) {/**
 * Sortables
 *
 * This makes certain settings sortable, and attempts to stash the results
 * in the nearest .cs-order input value.
 */
jQuery(document).ready(function ($) {
  var cs_sortables = $('ul.cs-sortable-list');

  if (cs_sortables.length > 0) {
    cs_sortables.sortable({
      axis: 'y',
      items: 'li',
      cursor: 'move',
      tolerance: 'pointer',
      containment: 'parent',
      distance: 2,
      opacity: 0.7,
      scroll: true,

      /**
       * When sorting stops, assign the value to the previous input.
       * This input should be a hidden text field
       */
      stop: function stop() {
        var keys = $.map($(this).children('li'), function (el) {
          return $(el).data('key');
        });
        $(this).prev('input.cs-order').val(keys);
      }
    });
  }
});
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery")))

/***/ }),

/***/ "./assets/js/admin/components/taxonomies/index.js":
/*!********************************************************!*\
  !*** ./assets/js/admin/components/taxonomies/index.js ***!
  \********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(jQuery) {/* global jQuery */
jQuery(document).ready(function ($) {
  if ($('body').hasClass('taxonomy-download_category') || $('body').hasClass('taxonomy-download_tag')) {
    $('.nav-tab-wrapper, .nav-tab-wrapper + br').detach().insertAfter('.wp-header-end');
  }
});
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery")))

/***/ }),

/***/ "./assets/js/admin/components/tooltips/index.js":
/*!******************************************************!*\
  !*** ./assets/js/admin/components/tooltips/index.js ***!
  \******************************************************/
/*! exports provided: cs_attach_tooltips */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function($, jQuery) {/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "cs_attach_tooltips", function() { return cs_attach_tooltips; });
/**
 * Attach tooltips
 *
 * @param {string} selector
 */
var cs_attach_tooltips = function cs_attach_tooltips(selector) {
  selector.tooltip({
    content: function content() {
      return $(this).prop('title');
    },
    tooltipClass: 'cs-ui-tooltip',
    position: {
      my: 'center top',
      at: 'center bottom+10',
      collision: 'flipfit'
    },
    hide: {
      duration: 200
    },
    show: {
      duration: 200
    }
  });
};
jQuery(document).ready(function ($) {
  cs_attach_tooltips($('.cs-help-tip'));
});
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery"), __webpack_require__(/*! jquery */ "jquery")))

/***/ }),

/***/ "./assets/js/admin/components/user-search/index.js":
/*!*********************************************************!*\
  !*** ./assets/js/admin/components/user-search/index.js ***!
  \*********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(jQuery) {jQuery(document).ready(function ($) {
  // AJAX user search
  $('.cs-ajax-user-search') // Search
  .keyup(function () {
    var user_search = $(this).val(),
        exclude = '';

    if ($(this).data('exclude')) {
      exclude = $(this).data('exclude');
    }

    $('.cs_user_search_wrap').addClass('loading');
    var data = {
      action: 'cs_search_users',
      user_name: user_search,
      exclude: exclude
    };
    $.ajax({
      type: 'POST',
      data: data,
      dataType: 'json',
      url: ajaxurl,
      success: function success(search_response) {
        $('.cs_user_search_wrap').removeClass('loading');
        $('.cs_user_search_results').removeClass('hidden');
        $('.cs_user_search_results span').html('');

        if (search_response.results) {
          $(search_response.results).appendTo('.cs_user_search_results span');
        }
      }
    });
  }) // Hide
  .blur(function () {
    if (cs_user_search_mouse_down) {
      cs_user_search_mouse_down = false;
    } else {
      $(this).removeClass('loading');
      $('.cs_user_search_results').addClass('hidden');
    }
  }) // Show
  .focus(function () {
    $(this).keyup();
  });
  $(document.body).on('click.eddSelectUser', '.cs_user_search_results span a', function (e) {
    e.preventDefault();
    var login = $(this).data('login');
    $('.cs-ajax-user-search').val(login);
    $('.cs_user_search_results').addClass('hidden');
    $('.cs_user_search_results span').html('');
  });
  $(document.body).on('click.eddCancelUserSearch', '.cs_user_search_results a.cs-ajax-user-cancel', function (e) {
    e.preventDefault();
    $('.cs-ajax-user-search').val('');
    $('.cs_user_search_results').addClass('hidden');
    $('.cs_user_search_results span').html('');
  }); // Cancel user-search.blur when picking a user

  var cs_user_search_mouse_down = false;
  $('.cs_user_search_results').mousedown(function () {
    cs_user_search_mouse_down = true;
  });
});
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery")))

/***/ }),

/***/ "./assets/js/admin/components/vertical-sections/index.js":
/*!***************************************************************!*\
  !*** ./assets/js/admin/components/vertical-sections/index.js ***!
  \***************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(jQuery) {jQuery(document).ready(function ($) {
  var sectionSelector = '.cs-vertical-sections.use-js'; // If the current screen doesn't have JS sections, return.

  if (0 === $(sectionSelector).length) {
    return;
  } // Hides the section content.


  $("".concat(sectionSelector, " .section-content")).hide();
  var hash = window.location.hash;

  if (hash && hash.includes('cs_')) {
    // Show the section content related to the URL.
    $(sectionSelector).find(hash).show(); // Set the aria-selected for section titles to be false

    $("".concat(sectionSelector, " .section-title")).attr('aria-selected', 'false').removeClass('section-title--is-active'); // Set aria-selected true on the related link.

    $(sectionSelector).find('.section-title a[href="' + hash + '"]').parents('.section-title').attr('aria-selected', 'true').addClass('section-title--is-active');
  } else {
    // Shows the first section's content.
    $("".concat(sectionSelector, " .section-content:first-child")).show(); // Makes the 'aria-selected' attribute true for the first section nav item.

    $("".concat(sectionSelector, " .section-nav li:first-child")).attr('aria-selected', 'true').addClass('section-title--is-active');
  } // When a section nav item is clicked.


  $("".concat(sectionSelector, " .section-nav li a")).on('click', function (j) {
    // Prevent the default browser action when a link is clicked.
    j.preventDefault(); // Get the `href` attribute of the item.

    var them = $(this),
        href = them.attr('href'),
        rents = them.parents('.cs-vertical-sections'); // Hide all section content.

    rents.find('.section-content').hide(); // Find the section content that matches the section nav item and show it.

    rents.find(href).show(); // Set the `aria-selected` attribute to false for all section nav items.

    rents.find('.section-title').attr('aria-selected', 'false').removeClass('section-title--is-active'); // Set the `aria-selected` attribute to true for this section nav item.

    them.parent().attr('aria-selected', 'true').addClass('section-title--is-active'); // Maybe re-Chosen

    rents.find('div.chosen-container').css('width', '100%'); // Add the current "link" to the page URL

    window.history.pushState('object or string', '', href);
  }); // click()
});
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery")))

/***/ }),

/***/ "./assets/js/admin/index.js":
/*!**********************************!*\
  !*** ./assets/js/admin/index.js ***!
  \**********************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _components_date_picker__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./components/date-picker */ "./assets/js/admin/components/date-picker/index.js");
/* harmony import */ var _components_date_picker__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_components_date_picker__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _components_chosen__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./components/chosen */ "./assets/js/admin/components/chosen/index.js");
/* harmony import */ var _components_tooltips__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./components/tooltips */ "./assets/js/admin/components/tooltips/index.js");
/* harmony import */ var _components_vertical_sections__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./components/vertical-sections */ "./assets/js/admin/components/vertical-sections/index.js");
/* harmony import */ var _components_vertical_sections__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_components_vertical_sections__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _components_sortable_list__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./components/sortable-list */ "./assets/js/admin/components/sortable-list/index.js");
/* harmony import */ var _components_sortable_list__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_components_sortable_list__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _components_user_search__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./components/user-search */ "./assets/js/admin/components/user-search/index.js");
/* harmony import */ var _components_user_search__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_components_user_search__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _components_advanced_filters__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./components/advanced-filters */ "./assets/js/admin/components/advanced-filters/index.js");
/* harmony import */ var _components_advanced_filters__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_components_advanced_filters__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _components_taxonomies__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./components/taxonomies */ "./assets/js/admin/components/taxonomies/index.js");
/* harmony import */ var _components_taxonomies__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_components_taxonomies__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _components_location__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./components/location */ "./assets/js/admin/components/location/index.js");
/* harmony import */ var _components_location__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_components_location__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _components_promos__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./components/promos */ "./assets/js/admin/components/promos/index.js");
/* harmony import */ var _components_promos__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_components_promos__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _components_notifications__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./components/notifications */ "./assets/js/admin/components/notifications/index.js");
/* harmony import */ var _components_notifications__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_components_notifications__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _orders_list_table__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./orders/list-table */ "./assets/js/admin/orders/list-table.js");
/**
 * Internal dependencies.
 */










 // Note: This is not common across all admin pages and at some point this code will be moved to a new file that only loads on the orders table page.



/***/ }),

/***/ "./assets/js/admin/orders/list-table.js":
/*!**********************************************!*\
  !*** ./assets/js/admin/orders/list-table.js ***!
  \**********************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function($) {/* harmony import */ var utils_jquery_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! utils/jquery.js */ "./assets/js/utils/jquery.js");
/* global $, ajaxurl */

/**
 * Internal dependencies
 */

Object(utils_jquery_js__WEBPACK_IMPORTED_MODULE_0__["jQueryReady"])(function () {
  $('.download_page_cs-payment-history .row-actions .delete a').on('click', function () {
    if (confirm(cs_vars.delete_payment)) {
      return true;
    }

    return false;
  });
});
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery")))

/***/ }),

/***/ "./assets/js/utils/chosen.js":
/*!***********************************!*\
  !*** ./assets/js/utils/chosen.js ***!
  \***********************************/
/*! exports provided: chosenVars, getChosenVars */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function(jQuery) {/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "chosenVars", function() { return chosenVars; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getChosenVars", function() { return getChosenVars; });
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);


function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }

/* global jQuery, cs_vars */
var chosenVars = {
  disable_search_threshold: 13,
  search_contains: true,
  inherit_select_classes: true,
  single_backstroke_delete: false,
  placeholder_text_single: cs_vars.one_option,
  placeholder_text_multiple: cs_vars.one_or_more_option,
  no_results_text: cs_vars.no_results_text
};
/**
 * Determine the variables used to initialie Chosen on an element.
 *
 * @param {Object} el select element.
 * @return {Object} Variables for Chosen.
 */

var getChosenVars = function getChosenVars(el) {
  if (!el instanceof jQuery) {
    el = jQuery(el);
  }

  var inputVars = chosenVars; // Ensure <select data-search-type="download"> or similar can use search always.
  // These types of fields start with no options and are updated via AJAX.

  if (el.data('search-type')) {
    delete inputVars.disable_search_threshold;
  }

  return _objectSpread(_objectSpread({}, inputVars), {}, {
    width: el.css('width')
  });
};
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

/***/ "jquery":
/*!*************************!*\
  !*** external "jQuery" ***!
  \*************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = jQuery;

/***/ })

/******/ });
//# sourceMappingURL=cs-admin.js.map