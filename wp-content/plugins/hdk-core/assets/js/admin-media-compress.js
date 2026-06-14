/**
 * Auto-compress oversized image uploads with full user-facing notifications.
 *
 * Hooks plupload.Uploader.prototype.trigger to intercept FilesAdded before
 * WordPress handlers. Shows Vietnamese-language notices for every state:
 * compressing, compressed, uploading, upload success, upload failure, rejection.
 *
 * Only reports upload success after parsing WordPress JSON response and
 * confirming data.success === true.
 *
 * Depends on: jQuery, wp-plupload (WordPress admin uploader bridge).
 */
(function($, plupload) {
    'use strict';

    var config = window._hdkMediaCompress || {};
    var hardLimitBytes = parseInt(config.hardLimitBytes, 10) || 2097152;
    var targetBytes = parseInt(config.targetBytes, 10) || 2031616;
    var sourceMaxBytes = parseInt(config.sourceMaxBytes, 10) || 52428800;
    var supportedTypes = config.supportedTypes || ['image/jpeg', 'image/png', 'image/webp'];

    if (sourceMaxBytes < hardLimitBytes) {
        sourceMaxBytes = hardLimitBytes;
    }

    var hasCanvas = (typeof document !== 'undefined' &&
        typeof document.createElement('canvas').getContext === 'function');
    var hasToBlob = (hasCanvas &&
        typeof HTMLCanvasElement.prototype.toBlob === 'function');
    var hasFileAPI = (typeof File !== 'undefined' && typeof Blob !== 'undefined');
    var hasURL = (typeof URL !== 'undefined' &&
        typeof URL.createObjectURL === 'function' &&
        typeof URL.revokeObjectURL === 'function');

    // ==================================================================
    //  NOTIFICATION SYSTEM
    // ==================================================================

    function ensureNoticeStyles() {
        if ($('#hdk-upload-notice-styles').length) return;
        $('head').append(
            '<style id="hdk-upload-notice-styles">' +
            '.hdk-upload-notices{position:absolute;top:14px;right:18px;z-index:100000;max-width:460px;width:calc(100% - 36px);pointer-events:none}' +
            '.hdk-upload-notice{display:none;margin:0 0 8px;padding:10px 12px;border-left:4px solid #2271b1;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,.16);font-size:13px;line-height:1.45;pointer-events:auto}' +
            '.hdk-upload-notice p{margin:0}' +
            '.hdk-notice-success{border-left-color:#00a32a}' +
            '.hdk-notice-error{border-left-color:#d63638}' +
            '.hdk-notice-info{border-left-color:#2271b1}' +
            '</style>'
        );
    }

    function getNoticeContainer() {
        var $notices = $('.media-modal .hdk-upload-notices, .hdk-upload-notices').first();
        if (!$notices.length) {
            $notices = $('<div class="hdk-upload-notices"></div>');
            var $target = $('.media-modal .media-frame-content').first();
            if (!$target.length) $target = $('.media-modal-content').first();
            if (!$target.length) $target = $('.upload-ui').first();
            if (!$target.length) $target = $('#wpbody-content').first();
            if (!$target.length) $target = $('body').first();
            if ($target.length) {
                $target.prepend($notices);
            }
        }
        return $notices;
    }

    function notify(type, message) {
        if (typeof document === 'undefined') {
            if (window.console && typeof window.console.log === 'function') {
                window.console.log('[HDK upload] ' + message);
            }
            return;
        }

        ensureNoticeStyles();
        var $notices = getNoticeContainer();
        if (!$notices.length) return;

        var $notice = $(
            '<div class="hdk-upload-notice hdk-notice-' + type + '">' +
            '<p>' + escapeHtml(message) + '</p>' +
            '</div>'
        );
        $notices.append($notice);
        $notice.slideDown(200);

        clearTimeout($notice.data('_hdkTimer'));
        $notice.data('_hdkTimer', setTimeout(function() {
            $notice.slideUp(200, function() { $notice.remove(); });
        }, 12000));

        if (typeof wp !== 'undefined' && wp.a11y && typeof wp.a11y.speak === 'function') {
            wp.a11y.speak(message, 'assertive');
        }
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // ==================================================================
    //  HELPERS
    // ==================================================================

    function formatSize(bytes) {
        if (!bytes) return '0 B';
        var i = Math.floor(Math.log(bytes) / Math.log(1024));
        return (bytes / Math.pow(1024, i)).toFixed(2) + ' ' + ['B', 'KB', 'MB', 'GB'][i];
    }

    function isSupportedImage(file) {
        var mime = (file.type || '').toLowerCase();
        if (supportedTypes.indexOf(mime) !== -1) return true;
        var ext = (file.name || '').split('.').pop().toLowerCase();
        var extMap = { jpg: 'image/jpeg', jpeg: 'image/jpeg', png: 'image/png', webp: 'image/webp' };
        return extMap[ext] && supportedTypes.indexOf(extMap[ext]) !== -1;
    }

    function getPluploadNativeFile(file) {
        return file && typeof file.getNative === 'function' ? file.getNative() : file;
    }

    function getFileName(file) {
        return (file && file.name) || 'file';
    }

    function buildFileItem(file) {
        var nativeFile = getPluploadNativeFile(file);
        var source = nativeFile || file || {};
        return {
            plFile: file,
            nativeFile: nativeFile,
            name: getFileName(file),
            size: source.size || file.size || 0,
            type: source.type || file.type || ''
        };
    }

    function getResponsePayload(response) {
        if (!response) return null;
        if (typeof response === 'string') return response;
        if (typeof response.response === 'string') return response.response;
        if (typeof response.responseText === 'string') return response.responseText;
        return response;
    }

    function getServerMessage(data) {
        if (!data) return '';
        if (typeof data === 'string') return data;
        if (data.data && typeof data.data === 'string') return data.data;
        if (data.data && data.data.message) return data.data.message;
        if (data.message) return data.message;
        return '';
    }

    function trackFile(up, file, message) {
        var name = getFileName(file);
        setupUploaderTracking(up);
        up._hdkTrackedFiles[name] = true;
        if (message) {
            notify('info', message);
        }
    }

    // ==================================================================
    //  IMAGE COMPRESSION
    // ==================================================================

    function compressImage(nativeFile, callback) {
        if (!hasFileAPI) {
            callback(new Error('Trinh duyet khong ho tro File API.'));
            return;
        }
        if (!hasCanvas || !hasToBlob) {
            callback(new Error('Trinh duyet khong ho tro xu ly anh Canvas.'));
            return;
        }
        if (!hasURL) {
            callback(new Error('Trinh duyet khong ho tro URL.createObjectURL.'));
            return;
        }

        var mime = nativeFile.type || '';
        if (!mime && nativeFile.name) {
            var ext = nativeFile.name.split('.').pop().toLowerCase();
            if (ext === 'png') mime = 'image/png';
            else if (ext === 'webp') mime = 'image/webp';
            else mime = 'image/jpeg';
        }

        var isPNG = (mime === 'image/png');

        var img, objectUrl;
        try {
            img = new Image();
            objectUrl = URL.createObjectURL(nativeFile);
        } catch (e) {
            callback(new Error('Khong the tao object URL cho "' + nativeFile.name + '".'));
            return;
        }

        img.onload = function() {
            URL.revokeObjectURL(objectUrl);

            var canvas, ctx;
            try {
                canvas = document.createElement('canvas');
                ctx = canvas.getContext('2d');
            } catch (e) {
                callback(new Error('Khong the tao canvas de nen anh.'));
                return;
            }

            var width = img.width;
            var height = img.height;
            var quality = isPNG ? 1.0 : 0.92;
            var attempt = 0;
            var maxAttempts = 30;

            function tryCompress() {
                if (attempt >= maxAttempts) {
                    callback(new Error(
                        'Khong the nen "' + nativeFile.name + '" xuong duoi ' +
                        formatSize(targetBytes) + '. Vui long dung anh nho hon.'
                    ));
                    return;
                }
                attempt++;

                try {
                    canvas.width = width;
                    canvas.height = height;
                    ctx.clearRect(0, 0, width, height);
                    ctx.drawImage(img, 0, 0, width, height);
                } catch (e) {
                    callback(new Error('Loi khi ve anh vao canvas.'));
                    return;
                }

                try {
                    canvas.toBlob(function(blob) {
                        if (!blob) {
                            callback(new Error('Trinh duyet khong ho tro xu ly anh.'));
                            return;
                        }

                        if (blob.size <= targetBytes) {
                            try {
                                var compressed = new File([blob], nativeFile.name, {
                                    type: mime,
                                    lastModified: Date.now()
                                });
                                callback(null, compressed);
                            } catch (e) {
                                callback(new Error('Khong the tao File sau khi nen.'));
                            }
                            return;
                        }

                        if (!isPNG && quality > 0.05) {
                            quality = Math.max(0.05, quality - 0.08);
                        } else if (width > 80 || height > 80) {
                            quality = isPNG ? 1.0 : 0.92;
                            width = Math.floor(width * 0.72);
                            height = Math.floor(height * 0.72);
                        } else {
                            callback(new Error(
                                'Khong the nen "' + nativeFile.name + '" xuong duoi ' +
                                formatSize(targetBytes) + '. Vui long dung anh nho hon.'
                            ));
                            return;
                        }

                        tryCompress();
                    }, mime, quality);
                } catch (e) {
                    callback(new Error('Loi khi goi canvas.toBlob.'));
                }
            }

            tryCompress();
        };

        img.onerror = function() {
            URL.revokeObjectURL(objectUrl);
            callback(new Error('Khong the doc file anh "' + nativeFile.name + '".'));
        };

        img.src = objectUrl;
    }

    // ==================================================================
    //  UPLOAD RESULT TRACKING
    // ==================================================================

    function setupUploaderTracking(up) {
        if (up._hdkTracking) return;
        up._hdkTracking = true;
        up._hdkTrackedFiles = {};

        up.bind('FileUploaded', function(_up, file, response) {
            var name = getFileName(file);
            if (!_up._hdkTrackedFiles[name]) return;
            delete _up._hdkTrackedFiles[name];

            var success = false;
            var serverMsg = '';

            try {
                var payload = getResponsePayload(response);
                var data = typeof payload === 'string' ? JSON.parse(payload) : payload;
                if (data && data.success === true) {
                    success = true;
                } else {
                    serverMsg = getServerMessage(data);
                }
            } catch (e) {
                serverMsg = getServerMessage(getResponsePayload(response));
            }

            if (success) {
                notify('success', 'Upload thanh cong: ' + name);
            } else {
                notify('error', 'Upload that bai: ' + name + (serverMsg ? ' - ' + serverMsg : ''));
            }
        });

        up.bind('Error', function(_up, err) {
            var name = getFileName(err && err.file);
            if (_up._hdkTrackedFiles[name]) {
                delete _up._hdkTrackedFiles[name];
            }
            if (!err || !err._hdkNotified) {
                notify('error', 'Upload that bai: ' + name + ' - ' + ((err && err.message) || 'Khong ro loi.'));
            }
        });
    }

    // ==================================================================
    //  FILESADDED INTERCEPTOR (called from trigger override)
    // ==================================================================

    function interceptFilesAdded(up, files) {
        setupUploaderTracking(up);

        var toCompress = [];
        var toReject = [];
        var validFiles = [];

        for (var i = 0; i < files.length; i++) {
            var f = buildFileItem(files[i]);

            if (f.size > sourceMaxBytes) {
                toReject.push(f);

            } else if (isSupportedImage(f) && f.size >= targetBytes) {
                toCompress.push(f);

            } else if (!isSupportedImage(f) && f.size >= hardLimitBytes) {
                toReject.push(f);

            } else {
                validFiles.push(f);
            }
        }

        if (toCompress.length === 0 && toReject.length === 0) {
            for (var pi = 0; pi < files.length; pi++) {
                trackFile(up, files[pi], 'Dang upload: ' + getFileName(files[pi]));
            }
            return;
        }

        for (var j = 0; j < files.length; j++) {
            up.removeFile(files[j]);
        }

        for (var r = 0; r < toReject.length; r++) {
            var rf = toReject[r];
            var limitStr = rf.size > sourceMaxBytes ? formatSize(sourceMaxBytes) : formatSize(hardLimitBytes);
            var msg = '"' + rf.name + '" vuot qua gioi han upload ' + limitStr + '.';
            notify('error', msg);
            up.trigger('Error', {
                message: msg,
                code: -61000,
                file: rf.plFile,
                _hdkNotified: true
            });
        }

        if (toCompress.length === 0) {
            up._hdkBypass = true;
            for (var vi = 0; vi < validFiles.length; vi++) {
                trackFile(up, validFiles[vi], 'Dang upload: ' + getFileName(validFiles[vi]));
                if (validFiles[vi].nativeFile) {
                    up.addFile(validFiles[vi].nativeFile);
                } else {
                    notify('error', 'Khong the doc file goc "' + validFiles[vi].name + '".');
                }
            }
            up._hdkBypass = false;
            return false;
        }

        for (var c = 0; c < toCompress.length; c++) {
            notify('info', 'Dang nen anh "' + toCompress[c].name + '" ... vui long cho.');
        }

        var pending = toCompress.length;
        var compressedFiles = [];
        var errorMessages = [];

        function onAllDone() {
            if (pending !== 0) return;

            for (var ei = 0; ei < errorMessages.length; ei++) {
                notify('error', errorMessages[ei]);
            }

            up._hdkBypass = true;
            for (var vi = 0; vi < validFiles.length; vi++) {
                trackFile(up, validFiles[vi], 'Dang upload: ' + getFileName(validFiles[vi]));
                if (validFiles[vi].nativeFile) {
                    up.addFile(validFiles[vi].nativeFile);
                } else {
                    notify('error', 'Khong the doc file goc "' + validFiles[vi].name + '".');
                }
            }
            for (var ci = 0; ci < compressedFiles.length; ci++) {
                notify('info', 'Da nen xong "' + compressedFiles[ci].name + '". Dang upload...');
                trackFile(up, compressedFiles[ci], null);
                up.addFile(compressedFiles[ci]);
            }
            up._hdkBypass = false;
        }

        for (var ci = 0; ci < toCompress.length; ci++) {
            (function(fileItem) {
                try {
                    if (!fileItem.nativeFile) {
                        throw new Error('Khong the doc file goc "' + fileItem.name + '".');
                    }
                    compressImage(fileItem.nativeFile, function(err, result) {
                        pending--;
                        if (err) {
                            errorMessages.push(err.message);
                        } else {
                            compressedFiles.push(result);
                        }
                        onAllDone();
                    });
                } catch (e) {
                    pending--;
                    errorMessages.push((e && e.message) || ('Loi he thong khi nen "' + fileItem.name + '".'));
                    onAllDone();
                }
            })(toCompress[ci]);
        }

        return false;
    }

    // ==================================================================
    //  PLUPLOAD PROTOTYPE OVERRIDE (trigger interceptor)
    // ==================================================================

    if (plupload && plupload.Uploader) {
        var origTrigger = plupload.Uploader.prototype.trigger;
        plupload.Uploader.prototype.trigger = function(name) {
            if (String(name).toLowerCase() === 'filesadded' && !this._hdkBypass) {
                var files = arguments[1];
                if (files && files.length > 0) {
                    if (interceptFilesAdded(this, files) === false) {
                        return false;
                    }
                }
            }
            return origTrigger.apply(this, arguments);
        };
    }

})(jQuery, typeof plupload !== 'undefined' ? plupload : null);
