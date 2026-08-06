/**
 * FC Admin — CodeMirror JSON editor for fence style DEV "Extra" tab.
 */
(function (global) {
    'use strict';

    var CM_VERSION = '5.65.16';
    var CM_BASE = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/' + CM_VERSION;
    var loadPromise = null;
    var loadedTheme = '';

    function isDarkTheme() {
        return document.documentElement.getAttribute('data-fc-admin-theme') === 'dark';
    }

    function editorTheme() {
        return isDarkTheme() ? 'dracula' : 'eclipse';
    }

    function loadStylesheet(href, id) {
        if (id && document.getElementById(id)) {
            return;
        }
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = href;
        if (id) {
            link.id = id;
        }
        document.head.appendChild(link);
    }

    function ensureThemeStyles() {
        var theme = editorTheme();
        if (loadedTheme === theme) {
            return;
        }
        loadStylesheet(CM_BASE + '/theme/' + theme + '.min.css', 'fc-cm-theme');
        loadedTheme = theme;
    }

    function loadScript(src) {
        return new Promise(function (resolve, reject) {
            if (document.querySelector('script[src="' + src + '"]')) {
                resolve();
                return;
            }
            var script = document.createElement('script');
            script.src = src;
            script.async = false;
            script.onload = function () {
                resolve();
            };
            script.onerror = function () {
                reject(new Error('Failed to load ' + src));
            };
            document.head.appendChild(script);
        });
    }

    function loadCodeMirror() {
        if (global.CodeMirror) {
            ensureThemeStyles();
            return Promise.resolve(global.CodeMirror);
        }
        if (loadPromise) {
            return loadPromise;
        }

        loadPromise = new Promise(function (resolve, reject) {
            loadStylesheet(CM_BASE + '/codemirror.min.css', 'fc-cm-core-css');
            ensureThemeStyles();

            loadScript(CM_BASE + '/codemirror.min.js')
                .then(function () {
                    return Promise.all([
                        loadScript(CM_BASE + '/mode/javascript/javascript.min.js'),
                        loadScript(CM_BASE + '/addon/edit/matchbrackets.min.js'),
                        loadScript(CM_BASE + '/addon/edit/closebrackets.min.js')
                    ]);
                })
                .then(function () {
                    resolve(global.CodeMirror);
                })
                .catch(function (err) {
                    loadPromise = null;
                    reject(err);
                });
        });

        return loadPromise;
    }

    function preload() {
        return loadCodeMirror().catch(function () {
            return null;
        });
    }

    function resolveEditRoot(container) {
        if (!container) {
            return null;
        }
        if (container.closest) {
            return container.closest('.fc-admin-fence-style-edit') || container;
        }
        return container;
    }

    function getWrap(container) {
        var root = resolveEditRoot(container);
        return root ? root.querySelector('.fc-fs-code-editor-wrap--full') : null;
    }

    function getTextarea(container) {
        var root = resolveEditRoot(container);
        return root ? root.querySelector('#fc-fs-full-json') : null;
    }

    function getEditor(container) {
        var wrap = getWrap(container);
        return wrap && wrap._fcCodeMirror ? wrap._fcCodeMirror : null;
    }

    function initFullJson(container, options) {
        options = options || {};
        var textarea = getTextarea(container);
        var wrap = textarea ? textarea.closest('.fc-fs-code-editor-wrap--full') : null;
        if (!textarea || !wrap) {
            return Promise.resolve(null);
        }

        if (wrap._fcCodeMirror) {
            ensureThemeStyles();
            wrap._fcCodeMirror.setOption('theme', editorTheme());
            return Promise.resolve(wrap._fcCodeMirror);
        }

        return loadCodeMirror()
            .then(function (CodeMirror) {
                if (wrap._fcCodeMirror) {
                    return wrap._fcCodeMirror;
                }

                var cm = CodeMirror.fromTextArea(textarea, {
                    mode: { name: 'javascript', json: true },
                    lineNumbers: true,
                    matchBrackets: true,
                    autoCloseBrackets: true,
                    theme: editorTheme(),
                    tabSize: 2,
                    indentUnit: 2,
                    indentWithTabs: false,
                    lineWrapping: true,
                    viewportMargin: 50
                });

                wrap._fcCodeMirror = cm;
                textarea.setAttribute('data-fc-cm-init', '1');

                if (typeof options.onChange === 'function') {
                    cm.on('change', options.onChange);
                }

                return cm;
            })
            .catch(function () {
                return null;
            });
    }

    function getValue(container) {
        var cm = getEditor(container);
        if (cm) {
            return cm.getValue();
        }
        var textarea = getTextarea(container);
        return textarea ? textarea.value : '';
    }

    function setValue(container, value) {
        var nextValue = value == null ? '' : String(value);
        var cm = getEditor(container);
        if (cm) {
            if (cm.getValue() !== nextValue) {
                cm.setValue(nextValue);
            }
            return;
        }
        var textarea = getTextarea(container);
        if (textarea) {
            textarea.value = nextValue;
        }
    }

    function setError(container, hasError) {
        var wrap = getWrap(container);
        if (wrap) {
            wrap.classList.toggle('fc-fs-code-editor-wrap--error', !!hasError);
        }
        var textarea = getTextarea(container);
        if (textarea) {
            textarea.classList.toggle('fc-fs-json-editor--error', !!hasError);
        }
    }

    function refresh(container) {
        var cm = getEditor(container);
        if (cm) {
            cm.refresh();
        }
    }

    function destroyInRoot(container) {
        var wrap = getWrap(container);
        if (!wrap || !wrap._fcCodeMirror) {
            return;
        }
        wrap._fcCodeMirror.toTextArea();
        wrap._fcCodeMirror = null;
        var textarea = getTextarea(container);
        if (textarea) {
            textarea.removeAttribute('data-fc-cm-init');
        }
    }

    global.addEventListener('fc-admin-theme-change', function () {
        ensureThemeStyles();
        document.querySelectorAll('.fc-fs-code-editor-wrap--full').forEach(function (wrap) {
            if (wrap._fcCodeMirror) {
                wrap._fcCodeMirror.setOption('theme', editorTheme());
            }
        });
    });

    global.FcFenceStyleCodeEditor = {
        preload: preload,
        initFullJson: initFullJson,
        getValue: getValue,
        setValue: setValue,
        setError: setError,
        refresh: refresh,
        destroyInRoot: destroyInRoot
    };
})(window);
