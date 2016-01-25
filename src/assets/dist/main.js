'use strict';

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var ReportingQueueItem = function () {
  function ReportingQueueItem() {
    _classCallCheck(this, ReportingQueueItem);

    this.timeouts = {};
  }

  _createClass(ReportingQueueItem, [{
    key: 'reportQueueItem',
    value: function reportQueueItem(queueItemId, params) {
      var options = ReportingQueueItem.defaultParams(params);
      var modal = this.initModal(options);
      var messageElement = modal.find('.reporting-queue-item__message');
      var outputElement = modal.find('.reporting-queue-item__output');
      var statusElement = modal.find('.reporting-queue-item__status');
      messageElement.text(options.requestingStatusMessage);
      modal.modal('show');
      ReportingQueueItem.requestAndUpdate(queueItemId, options.endpoint, options.outputRequestInterval, outputElement, statusElement, 0, options.afterCallback);
      var that = this;
      modal.on('hide.bs.modal', function onHideRemoveTimeout() {
        if (that.timeouts.hasOwnProperty(queueItemId)) {
          clearTimeout(that.timeouts[queueItemId]);
        }
      });
    }
  }, {
    key: 'initModal',
    value: function initModal(params) {
      var element = $('<div class="modal modal-notifier bounceInUp animated">');
      var dialog = $('<div class="modal-dialog modal-dialog--reporting-queue-item">');
      var dialogContent = $('<div class="modal-content">');

      var header = $('\n    <div class="modal-header with-border">\n      <div class="modal-tools pull-right">\n       <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>\n      </div>\n      <h4 class="modal-title">' + params.modalTitle + '</h4>\n    </div>');

      var body = $('\n    <div class="modal-body">\n      <div class="reporting-queue-item">\n        ' + params.statusLabel + ' <span class="reporting-queue-item__status">???</span>\n        <div class="reporting-queue-item__message"></div>\n        <pre class="reporting-queue-item__output"></pre>\n      </div>\n    </div>');

      // hide if timeout is set
      if (params.timeout !== false) {
        setTimeout(function hide() {
          // add bounce out animation
          element.removeClass('bounceInUp').addClass('bounceOutDown').one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function complete() {
            // remove element as soon as animation complete
            element.remove();
          });
        }, params.timeout);
      }

      dialogContent.append(header).append(body);
      dialog.append(dialogContent);

      element.append(dialog).hide();

      $('body').append(element);

      return element;
    }
  }, {
    key: 'executeRouteWithReportingQueueItem',
    value: function executeRouteWithReportingQueueItem(route) {
      var data = arguments.length <= 1 || arguments[1] === undefined ? {} : arguments[1];
      var method = arguments.length <= 2 || arguments[2] === undefined ? 'POST' : arguments[2];
      var params = arguments.length <= 3 || arguments[3] === undefined ? {} : arguments[3];

      var that = this;
      $.ajax({
        url: route,
        'data': data,
        'method': method,
        success: function ok(queueData) {
          if (queueData.queueItemId) {
            that.reportQueueItem(queueData.queueItemId, params);
          } else {
            alert('No queueItemId returned from application.');
          }
        },
        error: function errr(jqXHR, textStatus, errorThrown) {
          alert(textStatus + '\n' + errorThrown);
        }
      });
    }
  }], [{
    key: 'requestAndUpdate',
    value: function requestAndUpdate(queueItemId, endpoint, outputRequestInterval, outputElement, statusElement) {
      var lastFseekPosition = arguments.length <= 5 || arguments[5] === undefined ? 0 : arguments[5];
      var afterCallback = arguments.length <= 6 || arguments[6] === undefined ? undefined : arguments[6];

      var that = this;
      $.ajax({
        url: endpoint,
        data: {
          'queueItemId': queueItemId,
          'lastFseekPosition': lastFseekPosition
        },
        success: function success(data) {
          if (data.error) {
            outputElement.parent().find('.reporting-queue-item__message').text(data.errorMessage);
          }

          outputElement.append(data.newOutput);

          var height = outputElement[0].scrollHeight;

          outputElement.scrollTop(height);

          var statusText = '';
          switch (data.status) {
            case 0:
              statusText = 'disabled';
              break;
            case 1:
              statusText = 'scheduled';
              break;
            case 2:
              statusText = 'running';
              break;
            case 3:
              statusText = 'failed';
              break;
            case 4:
              statusText = 'complete';
              break;
            case 5:
              queueItemId = data.nextQueue;
              statusText = 'running next';
              break;
            default:
              statusText = 'unknown';
          }
          statusElement.text(statusText);

          if ([0, 1, 2, 5].indexOf(data.status) !== -1) {
            outputElement.parent().find('.reporting-queue-item__message').text('Processing');
            window.reportingQueueItem.timeouts[queueItemId] = setTimeout(function refresh() {
              ReportingQueueItem.requestAndUpdate(queueItemId, endpoint, outputRequestInterval, outputElement, statusElement, data.lastFseekPosition, afterCallback);
            }, outputRequestInterval);
          } else {
            outputElement.parent().find('.reporting-queue-item__message').text('Complete');
            if (afterCallback && afterCallback.constructor && afterCallback.call && afterCallback.apply) {
              afterCallback(outputElement, data.status);
            }
            if (that.timeouts && that.timeouts.hasOwnProperty(queueItemId)) {
              clearTimeout(that.timeouts[queueItemId]);
            }
          }
        }
      });
    }
  }, {
    key: 'defaultParams',
    value: function defaultParams(params) {
      return {
        'modalTitle': params.modalTitle || 'Background task',
        'closeButtonLabel': params.closeButtonLabel || 'Close',
        'statusLabel': params.statusLabel || 'Status:',
        'requestingStatusMessage': params.requestingStatusMessage || 'Requesting queue item information.',
        'outputRequestInterval': 1000,
        'endpoint': params.endpoint || '/deferred-report-queue-item',
        'afterCallback': params.afterCallback || undefined
      };
    }
  }]);

  return ReportingQueueItem;
}();

window.reportingQueueItem = new ReportingQueueItem();
//# sourceMappingURL=main.js.map
