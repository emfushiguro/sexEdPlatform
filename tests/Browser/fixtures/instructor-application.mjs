import fs from 'node:fs';

const fixturePath = process.env.PW_FIXTURES_FILE;
export const instructorFixtures = fixturePath && fs.existsSync(fixturePath)
    ? JSON.parse(fs.readFileSync(fixturePath, 'utf8'))
    : null;

export function fixture(name) {
    if (!instructorFixtures?.[name]) {
        throw new Error(`Missing sanitized browser fixture alias: ${name}`);
    }

    return instructorFixtures[name];
}
