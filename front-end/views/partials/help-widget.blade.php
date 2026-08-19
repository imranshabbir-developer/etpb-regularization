{{--
    Help widget.

    Most people using this portal are members of the public doing this once,
    and the questions they have — am I eligible, what will I owe, what happens
    next — are answerable from the Scheme itself. The widget answers from a
    curated knowledge base and cites the clause; where it cannot match a
    question confidently it says so and points to the district office rather
    than guessing about a legal matter.
--}}

<button type="button" class="help-launcher no-print" id="helpLauncher"
        aria-label="Ask about the scheme" aria-expanded="false" aria-controls="helpPanel">
    <span class="help-launcher-icon">@include('partials.icon', ['name' => 'chat'])</span>
    <span class="help-launcher-label">Ask about the scheme</span>
</button>

<section class="help-panel no-print" id="helpPanel" hidden aria-label="Scheme help">
    <header class="help-head pk-stripe">
        <div>
            <strong>Scheme help</strong>
            <span>Regularization of Possession</span>
        </div>
        <button type="button" class="help-close" id="helpClose" aria-label="Close help">
            @include('partials.icon', ['name' => 'close'])
        </button>
    </header>

    <div class="help-log" id="helpLog" role="log" aria-live="polite"></div>

    <div class="help-chips" id="helpChips"></div>

    <form class="help-form" id="helpForm">
        <label class="sr-only" for="helpInput">Your question</label>
        <input type="text" id="helpInput" class="help-input" autocomplete="off"
               placeholder="Type your question…" maxlength="500">
        <button type="submit" class="help-send" aria-label="Send">
            @include('partials.icon', ['name' => 'send'])
        </button>
    </form>

    <p class="help-foot">
        General guidance only. For your own case, contact your ETPB district office.
    </p>
</section>

<script>
(function () {
    var launcher = document.getElementById('helpLauncher');
    var panel    = document.getElementById('helpPanel');
    var closeBtn = document.getElementById('helpClose');
    var log      = document.getElementById('helpLog');
    var chips    = document.getElementById('helpChips');
    var form     = document.getElementById('helpForm');
    var input    = document.getElementById('helpInput');
    if (!launcher || !panel) return;

    var token   = document.querySelector('meta[name="csrf-token"]');
    var askUrl  = @json(route('assistant.ask'));
    var topUrl  = @json(route('assistant.topics'));
    var started = false;

    function esc(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    // The knowledge base uses **bold**, hyphen bullets and blank-line paragraphs.
    function render(text) {
        var blocks = esc(text).split(/\n{2,}/);
        return blocks.map(function (block) {
            var lines = block.split('\n');
            var bulleted = lines.every(function (l) { return /^\s*[-\d]/.test(l) || l.trim() === ''; })
                           && /^\s*[-\d]/.test(lines[0]);
            var html;
            if (bulleted) {
                html = '<ul>' + lines.filter(function (l) { return l.trim(); })
                    .map(function (l) { return '<li>' + l.replace(/^\s*(?:[-]|\d+\.)\s*/, '') + '</li>'; })
                    .join('') + '</ul>';
            } else {
                html = '<p>' + lines.join('<br>') + '</p>';
            }
            return html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        }).join('');
    }

    function bubble(who, html, clause) {
        var el = document.createElement('div');
        el.className = 'help-msg help-msg-' + who;
        el.innerHTML = html + (clause ? '<span class="clause help-clause">' + esc(clause) + '</span>' : '');
        log.appendChild(el);
        log.scrollTop = log.scrollHeight;
        return el;
    }

    function setChips(list) {
        chips.innerHTML = '';
        (list || []).forEach(function (t) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'help-chip';
            b.textContent = t;
            b.addEventListener('click', function () { send(t); });
            chips.appendChild(b);
        });
    }

    function send(question) {
        if (!question || !question.trim()) return;
        bubble('you', '<p>' + esc(question) + '</p>');
        input.value = '';
        setChips([]);

        var thinking = bubble('bot', '<p class="help-thinking">Looking that up…</p>');

        fetch(askUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token ? token.content : ''
            },
            body: JSON.stringify({ question: question })
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            thinking.remove();
            bubble('bot', render(d.answer), d.clause);
            setChips(d.suggestions);
        })
        .catch(function () {
            thinking.remove();
            bubble('bot', '<p>Sorry, I could not reach the help service. Please try again.</p>');
        });
    }

    function open() {
        panel.hidden = false;
        launcher.setAttribute('aria-expanded', 'true');
        document.body.classList.add('help-open');

        if (!started) {
            started = true;
            fetch(topUrl, { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    bubble('bot', render(d.greeting));
                    setChips(d.topics);
                })
                .catch(function () {
                    bubble('bot', '<p>Ask me about eligibility, the fee, documents, rent or arrears.</p>');
                });
        }
        setTimeout(function () { input.focus(); }, 60);
    }

    function close() {
        panel.hidden = true;
        launcher.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('help-open');
        launcher.focus();
    }

    launcher.addEventListener('click', function () { panel.hidden ? open() : close(); });
    closeBtn.addEventListener('click', close);
    form.addEventListener('submit', function (e) { e.preventDefault(); send(input.value); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !panel.hidden) close();
    });
})();
</script>
