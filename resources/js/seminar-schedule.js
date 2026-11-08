const civilMillis = value => value ? Date.parse(`${value.length === 16 ? value + ':00' : value}Z`) : NaN;

export function shiftCivil(value, minutes) {
    const timestamp = civilMillis(value);
    if (!Number.isFinite(timestamp)) return '';
    const result = new Date(timestamp + minutes * 60000).toISOString().slice(0, 19);
    return value.length === 16 && result.endsWith(':00') ? result.slice(0, 16) : result;
}

export function seminarSchedule(initial = {}) {
    return {
        starts_at: initial.starts_at ?? '',
        ends_at: initial.ends_at ?? '',
        registration_deadline_at: initial.registration_deadline_at ?? '',
        external_link_visible_at: initial.external_link_visible_at ?? '',
        automatic: { ends_at: !!initial.suggest, registration_deadline_at: !!initial.suggest, external_link_visible_at: !!initial.suggest },
        locked: !!initial.locked,
        init() { this.followStart(); },
        part(field, index) { return (this[`${field}Parts`] ?? this[field]?.split('T'))?.[index] ?? ''; },
        inputPart(field, index, value) {
            const parts = this[`${field}Parts`] ?? [this.part(field, 0), this.part(field, 1)];
            parts[index] = value;
            this[`${field}Parts`] = parts;
            this[field] = parts.every(Boolean) ? parts.join('T') : '';
            if (field !== 'starts_at') this.automatic[field] = false;
            if (field === 'starts_at') this.followStart();
        },
        followStart() {
            for (const [field, offset] of Object.entries({ ends_at: 60, registration_deadline_at: -30, external_link_visible_at: -15 })) {
                if (this.automatic[field]) { this[field] = shiftCivil(this.starts_at, offset); delete this[`${field}Parts`]; }
            }
        },
        suggest(field) { this.automatic[field] = true; this.followStart(); },
        duration(minutes) { this.automatic.ends_at = false; this.ends_at = shiftCivil(this.starts_at, minutes); delete this.ends_atParts; },
        timeStep(field) { return Number(this.part(field, 1).split(':')[2] ?? 0) ? 1 : 60; },
        get dateSummary() {
            const readable = value => {
                const timestamp = civilMillis(value);
                if (!Number.isFinite(timestamp)) return '';
                return new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'UTC' }).format(new Date(timestamp));
            };
            if (!this.starts_at || !this.ends_at) return '';
            return readable(this.starts_at) + ' to ' + readable(this.ends_at) + ' PHT';
        },
        get deadlineFeedback() {
            if (!this.starts_at || !this.registration_deadline_at) return '';
            return civilMillis(this.registration_deadline_at) >= civilMillis(this.starts_at) ? 'Registration deadline must be before event start.' : '';
        },
        get durationMinutes() { return (civilMillis(this.ends_at) - civilMillis(this.starts_at)) / 60000; },
        get feedback() {
            if (!this.starts_at || !this.ends_at) return '';
            return this.durationMinutes > 0 ? `Duration: ${this.durationMinutes} minutes` : 'End must be after start.';
        },
    };
}

if (typeof window !== 'undefined') window.seminarSchedule = seminarSchedule;
