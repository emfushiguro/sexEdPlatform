<div id="cc-page-translator" class="fixed bottom-4 right-4 z-[9998]">
    <button type="button"
            id="cc-translator-toggle"
            class="w-11 h-11 rounded-full border border-cyan-300 bg-white text-cyan-700 shadow-lg hover:shadow-xl hover:bg-cyan-50 transition-all"
            title="Page Translator">
        <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 5.5A18.022 18.022 0 0015.588 9m-9.176 0a18.022 18.022 0 013.636 5.5m3.952 0A18.022 18.022 0 0117.588 9M13 21l3-9 3 9m-5.2-3h4.4" />
        </svg>
    </button>

    <div id="cc-page-translator-panel" class="hidden mt-2 w-60 rounded-xl border border-cyan-200 bg-white/95 shadow-xl backdrop-blur-sm p-3">
        <div class="flex items-center justify-between mb-2">
            <p class="text-[11px] font-bold uppercase tracking-wide text-cyan-700">Page Translator</p>
            <button type="button" id="cc-restore-page-btn" class="text-[11px] font-semibold text-gray-500 hover:text-cyan-700 transition-colors">Restore</button>
        </div>

        <div class="flex items-center gap-2">
            <select id="cc-translator-lang" class="flex-1 text-sm rounded-lg border border-cyan-300 bg-white px-2.5 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-cyan-300/70">
                <option value="en">English</option>
                <option value="tl">Tagalog</option>
            </select>
            <button type="button" id="cc-translate-page-btn" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-white rounded-lg transition-all hover:opacity-90" style="background: linear-gradient(135deg, #0891b2, #2563eb);">
                Translate
            </button>
        </div>

        <p id="cc-translator-status" class="min-h-[16px] mt-2 text-[11px] text-gray-500"></p>
    </div>
</div>

<script>
(function () {
    if (window.__ccPageTranslatorLoaded) {
        return;
    }
    window.__ccPageTranslatorLoaded = true;

    document.addEventListener('DOMContentLoaded', function () {
        var wrapper = document.getElementById('cc-page-translator');
        if (!wrapper) {
            return;
        }

        var toggleBtn = document.getElementById('cc-translator-toggle');
        var panelEl = document.getElementById('cc-page-translator-panel');
        var translateBtn = document.getElementById('cc-translate-page-btn');
        var restoreBtn = document.getElementById('cc-restore-page-btn');
        var langSelect = document.getElementById('cc-translator-lang');
        var statusEl = document.getElementById('cc-translator-status');
        var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        var apiUrl = @json(route('learner.translator.page'));
        var storageKey = 'cc_page_translation_language';
        var isBusy = false;

        var originalNodeText = new Map();

        function setStatus(message, isError) {
            statusEl.textContent = message || '';
            statusEl.className = 'min-h-[16px] mt-2 text-[11px] ' + (isError ? 'text-red-600' : 'text-gray-500');
        }

        function collectTextNodes() {
            var nodes = [];
            var walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, {
                acceptNode: function (node) {
                    if (!node || typeof node.nodeValue !== 'string') {
                        return NodeFilter.FILTER_REJECT;
                    }

                    var parent = node.parentElement;
                    if (!parent) {
                        return NodeFilter.FILTER_REJECT;
                    }

                    if (parent.closest('#cc-page-translator')) {
                        return NodeFilter.FILTER_REJECT;
                    }

                    var tag = parent.tagName;
                    if (['SCRIPT', 'STYLE', 'NOSCRIPT', 'TEXTAREA', 'CODE', 'PRE', 'SVG', 'OPTION'].includes(tag)) {
                        return NodeFilter.FILTER_REJECT;
                    }

                    if (node.nodeValue.trim() === '') {
                        return NodeFilter.FILTER_REJECT;
                    }

                    return NodeFilter.FILTER_ACCEPT;
                }
            });

            while (walker.nextNode()) {
                nodes.push(walker.currentNode);
            }

            return nodes;
        }

        function chunk(array, size) {
            var output = [];
            for (var i = 0; i < array.length; i += size) {
                output.push(array.slice(i, i + size));
            }
            return output;
        }

        function uniqueTrimmedTexts(nodes) {
            var seen = new Set();
            var unique = [];

            nodes.forEach(function (node) {
                if (!originalNodeText.has(node)) {
                    originalNodeText.set(node, node.nodeValue);
                }

                var original = originalNodeText.get(node);
                var trimmed = original.trim();
                if (!trimmed || seen.has(trimmed)) {
                    return;
                }

                seen.add(trimmed);
                unique.push(trimmed);
            });

            return unique;
        }

        function applyTranslations(nodes, dictionary) {
            nodes.forEach(function (node) {
                var original = originalNodeText.get(node);
                if (!original) {
                    return;
                }

                var trimmed = original.trim();
                if (!trimmed) {
                    return;
                }

                var translated = dictionary.get(trimmed);
                if (!translated) {
                    return;
                }

                var leading = (original.match(/^\s*/) || [''])[0];
                var trailing = (original.match(/\s*$/) || [''])[0];
                node.nodeValue = leading + translated + trailing;
            });
        }

        function restorePage() {
            originalNodeText.forEach(function (original, node) {
                if (node && node.isConnected) {
                    node.nodeValue = original;
                }
            });
            localStorage.setItem(storageKey, 'en');
            langSelect.value = 'en';
            setStatus('Original page restored.', false);
        }

        async function requestBatch(texts, targetLanguage) {
            var response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    texts: texts,
                    target_language: targetLanguage,
                }),
            });

            var payload = {};
            try {
                payload = await response.json();
            } catch (error) {
                payload = {};
            }

            if (!response.ok) {
                throw new Error(payload.message || 'Page translation failed.');
            }

            if (!Array.isArray(payload.translated_texts)) {
                throw new Error('Invalid translation response.');
            }

            return payload.translated_texts;
        }

        async function translatePage(targetLanguage) {
            if (targetLanguage === 'en') {
                restorePage();
                return;
            }

            if (isBusy) {
                return;
            }

            isBusy = true;
            translateBtn.disabled = true;
            setStatus('Scanning page text...', false);

            try {
                var nodes = collectTextNodes();
                if (!nodes.length) {
                    setStatus('No translatable text found.', true);
                    return;
                }

                var uniqueTexts = uniqueTrimmedTexts(nodes);
                var textBatches = chunk(uniqueTexts, 120);
                var dictionary = new Map();

                for (var i = 0; i < textBatches.length; i++) {
                    setStatus('Translating page... ' + (i + 1) + '/' + textBatches.length, false);
                    var translatedBatch = await requestBatch(textBatches[i], targetLanguage);

                    textBatches[i].forEach(function (sourceText, index) {
                        dictionary.set(sourceText, translatedBatch[index] || sourceText);
                    });
                }

                applyTranslations(nodes, dictionary);
                localStorage.setItem(storageKey, targetLanguage);
                setStatus('Page translated.', false);
            } catch (error) {
                setStatus(error.message || 'Unable to translate the page right now.', true);
            } finally {
                isBusy = false;
                translateBtn.disabled = false;
            }
        }

        toggleBtn.addEventListener('click', function () {
            panelEl.classList.toggle('hidden');
        });

        document.addEventListener('click', function (event) {
            if (!wrapper.contains(event.target)) {
                panelEl.classList.add('hidden');
            }
        });

        translateBtn.addEventListener('click', function () {
            translatePage(langSelect.value);
        });

        restoreBtn.addEventListener('click', function () {
            restorePage();
        });

        var savedLanguage = localStorage.getItem(storageKey) || 'en';
        if (savedLanguage && langSelect.querySelector('option[value="' + savedLanguage + '"]')) {
            langSelect.value = savedLanguage;
        }

        if (savedLanguage !== 'en') {
            setTimeout(function () {
                translatePage(savedLanguage);
            }, 350);
        }
    });
})();
</script>
