import fs from 'node:fs';

export function requireBrowserFixtures() {
    const fixturePath = process.env.PW_FIXTURES_FILE;
    if (!fixturePath || !fs.existsSync(fixturePath)) {
        throw new Error('Set PW_FIXTURES_FILE to the sanitized fixture-alias JSON before authenticated browser tests.');
    }
}
