export function createOnboardingGuideModal(
    config,
    request = window.fetch.bind(window),
    notifyError = message => window.toast?.error(message),
) {
    return {
        saving: false,
        error: '',

        startWatching() {
            void this.$refs.video?.play();
        },

        dismiss() {
            this.$refs.dialog.close();
            void this.post(config.dismissUrl).catch(() => {
                notifyError('Your choice could not be saved. The guide may appear again.');
            });
        },

        async complete() {
            if (this.saving) {
                return;
            }

            this.saving = true;
            this.error = '';

            try {
                await this.post(config.completeUrl);
                this.$refs.dialog.close();
            } catch {
                this.error = 'Completion could not be saved. Try again or skip for now.';
            } finally {
                this.saving = false;
            }
        },

        async post(url) {
            const response = await request(url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                },
            });

            if (!response.ok) {
                throw new Error('Request failed');
            }

            return response.json();
        },
    };
}

if (typeof window !== 'undefined') {
    window.onboardingGuideModal = createOnboardingGuideModal;
}
