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
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/js/admin/upgrades/index.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/js/admin/upgrades/index.js":
/*!*******************************************!*\
  !*** ./assets/js/admin/upgrades/index.js ***!
  \*******************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _v3__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./v3 */ "./assets/js/admin/upgrades/v3/index.js");
/* harmony import */ var _v3__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_v3__WEBPACK_IMPORTED_MODULE_0__);


/***/ }),

/***/ "./assets/js/admin/upgrades/v3/index.js":
/*!**********************************************!*\
  !*** ./assets/js/admin/upgrades/v3/index.js ***!
  \**********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function($, jQuery) {var CS_v3_Upgrades = {
  inProgress: false,
  init: function init() {
    // Listen for toggle on the checkbox.
    $('.cs-v3-migration-confirmation').on('change', function (e) {
      var wrapperForm = $(this).closest('.cs-v3-migration');
      var formSubmit = wrapperForm.find('button');

      if (e.target.checked) {
        formSubmit.removeClass('disabled').prop('disabled', false);
      } else {
        formSubmit.addClass('disabled').prop('disabled', true);
      }
    });
    $('.cs-v3-migration').on('submit', function (e) {
      e.preventDefault();

      if (CS_v3_Upgrades.inProgress) {
        return;
      }

      CS_v3_Upgrades.inProgress = true;
      var migrationForm = $(this);
      var upgradeKeyField = migrationForm.find('input[name="upgrade_key"]');
      var upgradeKey = false;

      if (upgradeKeyField.length && upgradeKeyField.val()) {
        upgradeKey = upgradeKeyField.val();
      } // Disable submit button.


      migrationForm.find('button').removeClass('button-primary').addClass('button-secondary disabled updating-message').prop('disabled', true); // Disable checkbox.

      migrationForm.find('input').prop('disabled', true); // If this is the main migration, reveal the steps & mark the first non-complete item as in progress.

      if ('cs-v3-migration' === migrationForm.attr('id')) {
        $('#cs-migration-progress').removeClass('cs-hidden');
        var firstNonCompleteUpgrade = $('#cs-migration-progress li:not(.cs-upgrade-complete)');

        if (firstNonCompleteUpgrade.length && !upgradeKey) {
          upgradeKey = firstNonCompleteUpgrade.data('upgrade');
        }
      }

      CS_v3_Upgrades.processStep(upgradeKey, 1, migrationForm.find('input[name="_wpnonce"]').val());
    });
  },
  processStep: function processStep(upgrade_key, step, nonce) {
    var data = {
      action: 'cs_process_v3_upgrade',
      _ajax_nonce: nonce,
      upgrade_key: upgrade_key,
      step: step
    };
    CS_v3_Upgrades.clearErrors();

    if (upgrade_key) {
      CS_v3_Upgrades.markUpgradeInProgress(upgrade_key);
    }

    $.ajax({
      type: 'POST',
      data: data,
      url: ajaxurl,
      success: function success(response) {
        if (!response.success) {
          CS_v3_Upgrades.showError(upgrade_key, response.data);
          return;
        }

        if (response.data.upgrade_completed) {
          CS_v3_Upgrades.markUpgradeComplete(response.data.upgrade_processed); // If we just completed legacy data removal then we're all done!

          if ('v30_legacy_data_removed' === response.data.upgrade_processed) {
            CS_v3_Upgrades.legacyDataRemovalComplete();
            return;
          }
        } else if (response.data.percentage) {
          // Update percentage for the upgrade we just processed.
          CS_v3_Upgrades.updateUpgradePercentage(response.data.upgrade_processed, response.data.percentage);
        }

        if (response.data.next_upgrade && 'v30_legacy_data_removed' === response.data.next_upgrade && 'v30_legacy_data_removed' !== response.data.upgrade_processed) {
          CS_v3_Upgrades.inProgress = false; // Legacy data removal is next, which we do not start automatically.

          CS_v3_Upgrades.showLegacyDataRemoval();
        } else if (response.data.next_upgrade) {
          // Start the next upgrade (or continuation of current) automatically.
          CS_v3_Upgrades.processStep(response.data.next_upgrade, response.data.next_step, response.data.nonce);
        } else {
          CS_v3_Upgrades.inProgress = false;
          CS_v3_Upgrades.stopAllSpinners();
        }
      }
    }).fail(function (data) {// @todo
    });
  },
  clearErrors: function clearErrors() {
    $('.cs-v3-migration-error').addClass('cs-hidden').html('');
  },
  showError: function showError(upgradeKey, message) {
    var container = $('#cs-v3-migration');

    if ('v30_legacy_data_removed' === upgradeKey) {
      container = $('#cs-v3-remove-legacy-data');
    }

    var errorWrapper = container.find('.cs-v3-migration-error');
    errorWrapper.html('<p>' + message + '</p>').removeClass('cs-hidden'); // Stop processing and allow form resubmission.

    CS_v3_Upgrades.inProgress = false;
    container.find('input').prop('disabled', false);
    container.find('button').prop('disabled', false).addClass('button-primary').removeClass('button-secondary disabled updating-message');
  },
  markUpgradeInProgress: function markUpgradeInProgress(upgradeKey) {
    var upgradeRow = $('#cs-v3-migration-' + upgradeKey);

    if (!upgradeRow.length) {
      return;
    }

    var statusIcon = upgradeRow.find('.dashicons');

    if (statusIcon.length) {
      statusIcon.removeClass('dashicons-minus').addClass('dashicons-update');
    }

    upgradeRow.find('.cs-migration-percentage').removeClass('cs-hidden');
  },
  updateUpgradePercentage: function updateUpgradePercentage(upgradeKey, newPercentage) {
    var upgradeRow = $('#cs-v3-migration-' + upgradeKey);

    if (!upgradeRow.length) {
      return;
    }

    upgradeRow.find('.cs-migration-percentage-value').text(newPercentage);
  },
  markUpgradeComplete: function markUpgradeComplete(upgradeKey) {
    var upgradeRow = $('#cs-v3-migration-' + upgradeKey);

    if (!upgradeRow.length) {
      return;
    }

    upgradeRow.addClass('cs-upgrade-complete');
    var statusIcon = upgradeRow.find('.dashicons');

    if (statusIcon.length) {
      statusIcon.removeClass('dashicons-minus dashicons-update').addClass('dashicons-yes');
    }

    var statusLabel = upgradeRow.find('.cs-migration-status .screen-reader-text');

    if (statusLabel.length) {
      statusLabel.text(cs_admin_upgrade_vars.migration_complete);
    } // Update percentage to 100%;


    upgradeRow.find('.cs-migration-percentage-value').text(100);
  },
  showLegacyDataRemoval: function showLegacyDataRemoval() {
    // Un-spin the main submit button.
    $('#cs-v3-migration-button').removeClass('updating-message'); // Show the "migration complete" message.

    $('#cs-v3-migration-complete').removeClass('cs-hidden');
    var dataRemovalWrapper = $('#cs-v3-remove-legacy-data');

    if (!dataRemovalWrapper.length) {
      return;
    }

    dataRemovalWrapper.removeClass('cs-hidden');
  },
  legacyDataRemovalComplete: function legacyDataRemovalComplete() {
    var wrapper = $('#cs-v3-remove-legacy-data');

    if (!wrapper.length) {
      return;
    }

    wrapper.find('form').addClass('cs-hidden');
    wrapper.find('#cs-v3-legacy-data-removal-complete').removeClass('cs-hidden');
  },
  stopAllSpinners: function stopAllSpinners() {}
};
jQuery(document).ready(function ($) {
  CS_v3_Upgrades.init();
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
//# sourceMappingURL=cs-admin-upgrades.js.map