/**
 * Keeps the inbox live without letting message volume drive the refresh rate.
 *
 * This replaces a plain `echo-private:teams.X,.MessageReceived => '$refresh'`
 * Livewire listener. That fired one full component refresh — four queries and a
 * 25-row render — per inbound message, PER connected agent, so a busy team
 * multiplied a single message into a burst of identical rebuilds.
 *
 * Leading edge fires immediately, so a quiet inbox still updates instantly.
 * Trailing edge guarantees the last message of a burst is never dropped, which
 * a plain debounce-on-every-event would do.
 */
export default (teamId, intervalMs = 1000) => ({
    _timer: null,
    _lastRefresh: 0,
    _channel: null,
    _handler: null,

    init() {
        if (!window.Echo || !teamId) return;

        this._handler = () => this.scheduleRefresh();
        this._channel = window.Echo.private(`teams.${teamId}`);
        this._channel.listen('.MessageReceived', this._handler);
    },

    destroy() {
        clearTimeout(this._timer);
        this._timer = null;
        // stopListening, never leave() — global-notifications and the message
        // window share this channel.
        if (this._channel && this._handler) {
            this._channel.stopListening('.MessageReceived', this._handler);
        }
    },

    scheduleRefresh() {
        if (this._timer) return; // a trailing refresh is already queued

        const sinceLast = Date.now() - this._lastRefresh;

        if (sinceLast >= intervalMs) {
            this._lastRefresh = Date.now();
            this.$wire.$refresh();
            return;
        }

        this._timer = setTimeout(() => {
            this._timer = null;
            this._lastRefresh = Date.now();
            this.$wire.$refresh();
        }, intervalMs - sinceLast);
    },
});
