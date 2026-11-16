export function createLessonFullscreen({ document, storage, dispatch }) {
    const key = 'lesson-focus-mode';
    return {
        focused: storage.getItem(key) === '1',
        nativeActive: false,
        message: '',
        async toggle() {
            if (this.focused) {
                if (document.fullscreenElement === document.documentElement) await document.exitFullscreen();
                this.leave();
                return;
            }
            await this.enter();
        },
        async enter() {
            try {
                if (!document.documentElement.requestFullscreen) throw new Error('unsupported');
                await document.documentElement.requestFullscreen();
                this.focused = true;
                this.nativeActive = true;
                this.message = '';
                storage.setItem(key, '1');
                dispatch('lesson-focus-enter');
            } catch {
                this.message = 'Full screen is unavailable on this browser. You can continue learning here.';
            }
        },
        sync() {
            if (this.nativeActive && !document.fullscreenElement) this.leave();
        },
        leave() {
            if (!this.focused) return;
            this.focused = false;
            this.nativeActive = false;
            storage.removeItem(key);
            dispatch('lesson-focus-exit');
        },
    };
}
