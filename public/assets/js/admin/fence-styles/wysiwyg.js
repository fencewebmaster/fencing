/**
 * FC Admin — TinyMCE WYSIWYG for fence style HTML fields.
 */
(function (global) {
    'use strict';

    var TINYMCE_SRC = 'https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js';
    var loadPromise = null;

    function isDarkTheme() {
        return document.documentElement.getAttribute('data-fc-admin-theme') === 'dark';
    }

    function serializeTextareaValue(val) {
        return String(val == null ? '' : val).replace(/<\/textarea/gi, '<\\/textarea');
    }

    function loadTinyMce() {
        if (global.tinymce) {
            return Promise.resolve(global.tinymce);
        }
        if (loadPromise) {
            return loadPromise;
        }
        loadPromise = new Promise(function (resolve, reject) {
            var script = document.createElement('script');
            script.src = TINYMCE_SRC;
            script.referrerPolicy = 'origin';
            script.onload = function () {
                resolve(global.tinymce);
            };
            script.onerror = function () {
                loadPromise = null;
                reject(new Error('Failed to load TinyMCE.'));
            };
            document.head.appendChild(script);
        });
        return loadPromise;
    }

    function getEditorConfig(textarea, onChange) {
        var dark = isDarkTheme();
        return {
            target: textarea,
            license_key: 'gpl',
            menubar: false,
            statusbar: false,
            branding: false,
            promotion: false,
            plugins: 'lists link code autoresize',
            toolbar:
                'undo redo | bold italic underline | bullist numlist | link | removeformat | code',
            autoresize_bottom_margin: 12,
            min_height: 160,
            max_height: 420,
            skin: dark ? 'oxide-dark' : 'oxide',
            content_css: dark ? 'dark' : 'default',
            entity_encoding: 'raw',
            convert_urls: false,
            setup: function (editor) {
                editor.on('change input undo redo', function () {
                    editor.save();
                    if (typeof onChange === 'function') {
                        onChange(textarea, editor.getContent());
                    }
                });
            }
        };
    }

    function initInRoot(root, onChange) {
        root = root || document;
        if (!root.querySelector) {
            return Promise.resolve([]);
        }

        var areas = root.querySelectorAll('textarea[data-fc-wysiwyg]:not([data-fc-wysiwyg-bound])');
        if (!areas.length) {
            return Promise.resolve([]);
        }

        return loadTinyMce()
            .then(function (tinymce) {
                var jobs = [];
                areas.forEach(function (textarea) {
                    if (!textarea.id) {
                        textarea.id = 'fc-wysiwyg-' + Math.random().toString(36).slice(2, 9);
                    }
                    textarea.setAttribute('data-fc-wysiwyg-bound', '1');
                    jobs.push(
                        new Promise(function (resolve) {
                            var finished = false;
                            tinymce.init(
                                Object.assign({}, getEditorConfig(textarea, onChange), {
                                    init_instance_callback: function () {
                                        if (!finished) {
                                            finished = true;
                                            resolve();
                                        }
                                    }
                                })
                            );
                        })
                    );
                });
                return Promise.all(jobs);
            })
            .catch(function () {
                return [];
            });
    }

    function syncAll(root) {
        if (!global.tinymce) {
            return;
        }
        global.tinymce.triggerSave();
        if (root && root.querySelectorAll) {
            root.querySelectorAll('textarea[data-fc-wysiwyg-bound]').forEach(function (textarea) {
                var editor = global.tinymce.get(textarea.id);
                if (editor) {
                    textarea.value = editor.getContent();
                }
            });
        }
    }

    function destroyInRoot(root) {
        root = root || document;
        if (!global.tinymce || !root.querySelectorAll) {
            return;
        }

        root.querySelectorAll('textarea[data-fc-wysiwyg-bound]').forEach(function (textarea) {
            var editor = global.tinymce.get(textarea.id);
            if (editor) {
                editor.save();
                editor.remove();
            }
            textarea.removeAttribute('data-fc-wysiwyg-bound');
        });
    }

    global.FcFenceStyleWysiwyg = {
        serializeTextareaValue: serializeTextareaValue,
        initInRoot: initInRoot,
        syncAll: syncAll,
        destroyInRoot: destroyInRoot
    };
})(window);
