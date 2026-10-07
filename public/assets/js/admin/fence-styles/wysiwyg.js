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

    var RELEASE_NOTES_TEMPLATE =
        '<h3>New Features</h3><ul><li>&nbsp;</li></ul>' +
        '<h3>Improvements</h3><ul><li>&nbsp;</li></ul>' +
        '<h3>Fixes</h3><ul><li>&nbsp;</li></ul>';

    // data-fc-wysiwyg="<profile>" swaps in a fuller toolbar; "1" keeps the fence-style defaults below.
    var PROFILES = {
        // Version Manager release notes: valid_elements mirrors HtmlSanitizer, so the editor never shows what saving drops.
        'release-notes': {
            options: {
                plugins: 'lists link code autoresize table codesample',
                toolbar:
                    'undo redo | blocks | bold italic | link | bullist numlist | ' +
                    'blockquote fcinlinecode codesample | table | fcnotestemplate | removeformat code',
                block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3; Heading 4=h4',
                toolbar_mode: 'wrap',
                valid_elements:
                    'p,br,hr,h2,h3,h4,strong/b,em/i,u,s,sub,sup,a[href|title|target],ul,ol[start],li,' +
                    'blockquote,code[class],pre[class],table,caption,thead,tbody,tfoot,tr,' +
                    'th[colspan|rowspan|scope],td[colspan|rowspan]',
                min_height: 280,
                max_height: 560,
                link_target_list: [
                    { title: 'Same window', value: '' },
                    { title: 'New window', value: '_blank' }
                ],
                link_assume_external_targets: 'https',
                paste_data_images: false,
                object_resizing: false,
                table_sizing_mode: 'responsive',
                table_resize_bars: false,
                table_appearance_options: false,
                table_advtab: false,
                table_cell_advtab: false,
                table_row_advtab: false,
                table_default_attributes: {},
                table_default_styles: {},
                codesample_languages: [
                    { text: 'Plain text', value: 'none' },
                    { text: 'HTML', value: 'markup' },
                    { text: 'CSS', value: 'css' },
                    { text: 'JavaScript', value: 'javascript' },
                    { text: 'PHP', value: 'php' },
                    { text: 'SQL', value: 'sql' },
                    { text: 'JSON', value: 'json' },
                    { text: 'Shell', value: 'bash' }
                ],
                content_style:
                    'body{font-family:Inter,system-ui,sans-serif;font-size:14px;line-height:1.6}' +
                    'code{padding:1px 4px;border-radius:3px;background:rgba(100,116,139,.15);font-size:.9em}' +
                    'pre{padding:10px 12px;border-radius:3px;background:rgba(100,116,139,.12);overflow:auto}' +
                    'pre code{padding:0;background:none}' +
                    'blockquote{margin:0 0 1em;padding:2px 0 2px 12px;border-left:3px solid #f67925}' +
                    'table{border-spacing:0;width:100%}th,td{padding:6px 8px;text-align:left;border-bottom:1px solid rgba(100,116,139,.35)}'
            },
            setup: function (editor) {
                editor.ui.registry.addToggleButton('fcinlinecode', {
                    icon: 'sourcecode',
                    tooltip: 'Inline code',
                    onAction: function () {
                        editor.execCommand('mceToggleFormat', false, 'code');
                    },
                    onSetup: function (api) {
                        var watch = editor.formatter.formatChanged('code', function (state) {
                            api.setActive(state);
                        });
                        return function () {
                            watch.unbind();
                        };
                    }
                });
                editor.ui.registry.addButton('fcnotestemplate', {
                    text: 'Sections',
                    tooltip: 'Insert New Features / Improvements / Fixes headings',
                    onAction: function () {
                        editor.insertContent(RELEASE_NOTES_TEMPLATE);
                    }
                });
            }
        }
    };

    function getEditorConfig(textarea, onChange) {
        var dark = isDarkTheme();
        var config = {
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

        var profile = PROFILES[textarea.getAttribute('data-fc-wysiwyg')];
        if (profile) {
            var baseSetup = config.setup;
            Object.assign(config, profile.options);
            config.setup = function (editor) {
                baseSetup(editor);
                profile.setup(editor);
            };
        }

        return config;
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
