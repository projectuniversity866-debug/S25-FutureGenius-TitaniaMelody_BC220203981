// Runs axe-core's WCAG 2.1 A/AA rules on saved HTML pages (roadmap 4.3).
//
//   node tests/a11y/axe-run.mjs page1.html page2.html …
//
// jsdom has no layout engine, so the rules that need rendering (colour
// contrast, target size) are left to the browser checks and the token
// contrast test (AccessibilityAuditTest WA4). Exit code 1 if any page has a
// violation.
import { readFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import { basename } from 'node:path';
import { JSDOM, VirtualConsole } from 'jsdom';

const axeSource = readFileSync(createRequire(import.meta.url).resolve('axe-core/axe.min.js'), 'utf8');
const files = process.argv.slice(2);
let violations = 0;
let passes = 0;

for (const file of files) {
    const dom = new JSDOM(readFileSync(file, 'utf8'), { runScripts: 'outside-only', pretendToBeVisual: true, virtualConsole: new VirtualConsole() });
    dom.window.eval(axeSource);
    const result = await dom.window.axe.run(dom.window.document, {
        runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'] },
        rules: { 'color-contrast': { enabled: false }, 'target-size': { enabled: false } },
    });
    passes += result.passes.length;
    if (result.violations.length) {
        console.log(`✗ ${basename(file)}`);
        for (const v of result.violations) {
            violations++;
            console.log(`    ${v.id} (${v.impact}): ${v.help} — ${v.nodes.length} element(s), e.g. ${v.nodes[0].target.join(' ')}`);
        }
    } else {
        console.log(`✓ ${basename(file)} — ${result.passes.length} rules passed`);
    }
    dom.window.close();
}

console.log(`\naxe: ${files.length} pages, ${violations} violation(s), ${passes} rule passes`);
process.exit(violations ? 1 : 0);
