const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');

for (const file of ['index.blade.php', 'form.blade.php']) {
    const view = fs.readFileSync(path.join(__dirname, '../../resources/views/suppliers', file), 'utf8');
    const fields = [...view.matchAll(/<input\b(?:[^>"']|"[^"]*"|'[^']*')*>/g)]
        .map((match) => match[0])
        .filter((field) => /\bname="email"/.test(field));

    test(`${file} email fields accept Gmail, Yahoo and Outlook with complete domain endings`, () => {
        assert.equal(fields.length, file === 'index.blade.php' ? 2 : 1);
        for (const field of fields) {
            const pattern = field.match(/\bpattern="([^"]+)"/);
            assert.ok(pattern, 'Every supplier email input must have domain validation.');
            const validator = new RegExp(`^(?:${pattern[1]})$`, 'v');
            for (const email of ['supplier@gmail.com', 'supplier@yahoo.com', 'supplier@outlook.com', 'Supplier@GMAIL.COM', 'Supplier@YAHOO.COM', 'Supplier@OUTLOOK.COM', 'supplier+orders@gmail.com']) {
                assert.equal(validator.test(email), true, `${email} should pass.`);
            }
            for (const email of ['TrustHardware@gmail', 'supplier@yahoo', 'supplier@outlook', 'Archive test@gmail.com', 'supplier@gamil.com', 'supplier@gmail.co', 'supplier@example.com', 'supplier@mail.gmail.com', 'supplier@gmail.com.example.com', 'supplier@@gmail.com']) {
                assert.equal(validator.test(email), false, `${email} should fail.`);
            }
        }
    });
}
