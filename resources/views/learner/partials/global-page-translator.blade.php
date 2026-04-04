<div id="cc-page-translator" class="fixed bottom-4 right-4 z-[9998] w-72 rounded-2xl border border-cyan-200 bg-white/95 shadow-xl backdrop-blur-sm p-3">
    <div class="flex items-center justify-between mb-2">
        <p class="text-xs font-bold uppercase tracking-wide text-cyan-700">Page Translator</p>
        <button type="button" id="cc-restore-page-btn" class="text-[11px] font-semibold text-gray-500 hover:text-cyan-700 transition-colors">Restore</button>
    </div>

    <div class="flex items-center gap-2 mb-2">
        <select id="cc-translator-lang" class="flex-1 text-sm rounded-lg border border-cyan-300 bg-white px-2.5 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-cyan-300/70">
            <option value="en">English (Original)</option>
            <option value="tl">Filipino (Tagalog)</option>
            <option value="ceb">Cebuano</option>
            <option value="ilo">Ilocano</option>
            <option value="es">Spanish</option>
        </select>
        <button type="button" id="cc-translate-page-btn" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-white rounded-lg transition-all hover:opacity-90" style="background: linear-gradient(135deg, #0891b2, #2563eb);">
            Translate
        </button>
    </div>

    <div class="flex items-center gap-2 mb-2">
        <button type="button" id="cc-read-page-btn" class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-semibold text-white rounded-lg transition-all hover:opacity-90" style="background: linear-gradient(135deg, #0e9f6e, #2563eb);">
            Read Page
        </button>
        <button type="button" id="cc-stop-read-btn" class="px-3 py-2 text-xs font-semibold rounded-lg border border-cyan-300 text-cyan-800 hover:bg-cyan-100 transition-colors">
            Stop
        </button>
    </div>

    <p id="cc-translator-status" class="min-h-[16px] text-[11px] text-gray-500"></p>
    <audio id="cc-page-audio" class="hidden" preload="none"></audio>
</div>

<script>
(function () {
    if (window.__ccPageTranslatorLoaded) {
        return;
    }
    window.__ccPageTranslatorLoaded = true;

    document.addEventListener('DOMContentLoaded', function () {
        var panel = document.getElementById('cc-page-translator');
        if (!panel) {
            return;
        }

        var translateBtn = document.getElementById('cc-translate-page-btn');
        var restoreBtn = document.getElementById('cc-restore-page-btn');
        var langSelect = document.getElementById('cc-translator-lang');
        var statusEl = document.getElementById('cc-translator-status');
        var readBtn = document.getElementById('cc-read-page-btn');
        var stopBtn = document.getElementById('cc-stop-read-btn');
        var pageAudio = document.getElementById('cc-page-audio');
        var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        var apiUrl = @json(route('learner.translator.page'));
        var ttsApiUrl = @json(route('learner.translator.tts'));
        var storageKey = 'cc_page_translation_language';
        var isBusy = false;
        var isReading = false;

        // Keep original text for reliable restore.
        var originalNodeText = new Map();

        function setStatus(message, isError) {
            statusEl.textContent = message || '';
            statusEl.className = 'min-h-[16px] text-[11px] ' + (isError ? 'text-red-600' : 'text-gray-500');
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

        function collectReadableTexts() {
            var nodes = collectTextNodes();
            var readable = [];
            var seen = new Set();

            for (var i = 0; i < nodes.length; i++) {
                var value = (nodes[i].nodeValue || '').replace(/\s+/g, ' ').trim();
                if (!value || seen.has(value)) {
                    continue;
                }

                seen.add(value);
                readable.push(value);
            }

            return readable;
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

        function mapTranslatorLanguageToTtsCode(language) {
            var map = {
                en: 'en-US',
                tl: 'fil-PH',
                ceb: 'fil-PH',
                ilo: 'fil-PH',
                es: 'es-ES'
            };

            return map[language] || 'en-US';
        }

        async function requestTtsAudio(texts, language) {
            var response = await fetch(ttsApiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    texts: texts,
                    language_code: mapTranslatorLanguageToTtsCode(language),
                    speaking_rate: 1.0,
                }),
            });

            var payload = {};
            try {
                payload = await response.json();
            } catch (error) {
                payload = {};
            }

            if (!response.ok) {
                throw new Error(payload.message || 'Unable to generate audio.');
            }

            var urls = [];
            if (typeof payload.audio_url === 'string' && payload.audio_url.trim() !== '') {
                urls.push(payload.audio_url.trim());
            }

            if (typeof payload.audio_relative_url === 'string' && payload.audio_relative_url.trim() !== '') {
                urls.push(payload.audio_relative_url.trim());
            }

            if (!urls.length) {
                throw new Error('Audio URL was not returned by the server.');
            }

            return Array.from(new Set(urls));
        }

        async function playAudioFromUrls(audioUrls) {
            var lastError = null;

            for (var i = 0; i < audioUrls.length; i++) {
                var audioUrl = audioUrls[i];
                if (!audioUrl) {
                    continue;
                }

                pageAudio.src = audioUrl + (audioUrl.includes('?') ? '&' : '?') + 'v=' + Date.now();
                pageAudio.load();

                try {
                    await pageAudio.play();
                    return;
                } catch (error) {
                    lastError = error;
                }
            }

            throw lastError || new Error('Unable to play generated audio.');
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

        async function readCurrentPage() {
            if (isReading) {
                return;
            }

            var texts = collectReadableTexts();
            if (!texts.length) {
                setStatus('No readable text found on this page.', true);
                return;
            }

            isReading = true;
            readBtn.disabled = true;
            setStatus('Generating speech audio...', false);

            try {
                var limitedTexts = texts.slice(0, 120);
                var audioUrls = await requestTtsAudio(limitedTexts, langSelect.value);

                await playAudioFromUrls(audioUrls);
                setStatus('Playing page audio...', false);
            } catch (error) {
                setStatus(error.message || 'Unable to read page right now.', true);
                isReading = false;
                readBtn.disabled = false;
            }
        }

        function stopReading() {
            if (!pageAudio) {
                return;
            }

            pageAudio.pause();
            pageAudio.currentTime = 0;
            isReading = false;
            readBtn.disabled = false;
            setStatus('Audio stopped.', false);
        }

        translateBtn.addEventListener('click', function () {
            translatePage(langSelect.value);
        });

        restoreBtn.addEventListener('click', function () {
            restorePage();
        });

        readBtn.addEventListener('click', function () {
            readCurrentPage();
        });

        stopBtn.addEventListener('click', function () {
            stopReading();
        });

        pageAudio.addEventListener('ended', function () {
            isReading = false;
            readBtn.disabled = false;
            setStatus('Audio finished.', false);
        });

        pageAudio.addEventListener('error', function () {
            isReading = false;
            readBtn.disabled = false;
            setStatus('Audio playback failed.', true);
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
