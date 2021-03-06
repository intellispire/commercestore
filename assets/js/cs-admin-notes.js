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
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/js/admin/notes/index.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/js/admin/notes/index.js":
/*!****************************************!*\
  !*** ./assets/js/admin/notes/index.js ***!
  \****************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function($, jQuery) {/**
 * Notes
 */
var CS_Notes = {
  init: function init() {
    this.enter_key();
    this.add_note();
    this.remove_note();
  },
  enter_key: function enter_key() {
    $(document.body).on('keydown', '#cs-note', function (e) {
      if (e.keyCode === 13 && (e.metaKey || e.ctrlKey)) {
        e.preventDefault();
        $('#cs-add-note').click();
      }
    });
  },

  /**
   * Ajax handler for adding new notes
   *
   * @since 3.0
   */
  add_note: function add_note() {
    $('#cs-add-note').on('click', function (e) {
      e.preventDefault();
      var cs_button = $(this),
          cs_note = $('#cs-note'),
          cs_notes = $('.cs-notes'),
          cs_no_notes = $('.cs-no-notes'),
          cs_spinner = $('.cs-add-note .spinner'),
          cs_note_nonce = $('#cs_note_nonce');
      var postData = {
        action: 'cs_add_note',
        nonce: cs_note_nonce.val(),
        object_id: cs_button.data('object-id'),
        object_type: cs_button.data('object-type'),
        note: cs_note.val()
      };

      if (postData.note) {
        cs_button.prop('disabled', true);
        cs_spinner.css('visibility', 'visible');
        $.ajax({
          type: 'POST',
          data: postData,
          url: ajaxurl,
          success: function success(response) {
            var res = wpAjax.parseAjaxResponse(response);
            res = res.responses[0];
            cs_notes.append(res.data);
            cs_no_notes.hide();
            cs_button.prop('disabled', false);
            cs_spinner.css('visibility', 'hidden');
            cs_note.val('');
          }
        }).fail(function (data) {
          if (window.console && window.console.log) {
            console.log(data);
          }

          cs_button.prop('disabled', false);
          cs_spinner.css('visibility', 'hidden');
        });
      } else {
        var border_color = cs_note.css('border-color');
        cs_note.css('border-color', 'red');
        setTimeout(function () {
          cs_note.css('border-color', border_color);
        }, userInteractionInterval);
      }
    });
  },

  /**
   * Ajax handler for deleting existing notes
   *
   * @since 3.0
   */
  remove_note: function remove_note() {
    $(document.body).on('click', '.cs-delete-note', function (e) {
      e.preventDefault();
      var cs_link = $(this),
          cs_notes = $('.cs-note'),
          cs_note = cs_link.parents('.cs-note'),
          cs_no_notes = $('.cs-no-notes'),
          cs_note_nonce = $('#cs_note_nonce');

      if (confirm(cs_vars.delete_note)) {
        var postData = {
          action: 'cs_delete_note',
          nonce: cs_note_nonce.val(),
          note_id: cs_link.data('note-id')
        };
        cs_note.addClass('deleting');
        $.ajax({
          type: 'POST',
          data: postData,
          url: ajaxurl,
          success: function success(response) {
            if ('1' === response) {
              cs_note.remove();
            }

            if (cs_notes.length === 1) {
              cs_no_notes.show();
            }

            return false;
          }
        }).fail(function (data) {
          if (window.console && window.console.log) {
            console.log(data);
          }

          cs_note.removeClass('deleting');
        });
        return true;
      }
    });
  }
};
jQuery(document).ready(function ($) {
  CS_Notes.init();
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
//# sourceMappingURL=cs-admin-notes.js.map