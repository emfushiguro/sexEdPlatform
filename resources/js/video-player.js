const BASE_CONTROLS = [
    'play-large',
    'play',
    'progress',
    'current-time',
    'mute',
    'volume',
    'settings',
    'fullscreen',
];

export function plyrOptionsFor(video) {
    const tracks = Array.from(video.querySelectorAll(
        'track[kind="subtitles"], track[kind="captions"]',
    ));
    const hasCaptions = tracks.length > 0;
    const defaultTrack = tracks.find((track) => track.default);
    const controls = [...BASE_CONTROLS];

    if (hasCaptions) {
        controls.splice(6, 0, 'captions');
    }

    const options = {
        speed: { selected: 1, options: [0.5, 0.75, 1, 1.25, 1.5, 2] },
        controls,
        settings: hasCaptions ? ['captions', 'speed'] : ['speed'],
    };

    if (hasCaptions) {
        options.captions = {
            active: Boolean(defaultTrack),
            language: defaultTrack?.srclang || 'auto',
            update: false,
        };
    }

    return options;
}
