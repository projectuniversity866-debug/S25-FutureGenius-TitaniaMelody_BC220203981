/*
 * Syntax highlighting for code blocks in rendered Markdown. Only the
 * languages the community is likely to use are bundled, to keep the
 * download small; unlabelled blocks are auto-detected among them.
 */
import hljs from 'highlight.js/lib/core';
import bash from 'highlight.js/lib/languages/bash';
import css from 'highlight.js/lib/languages/css';
import javascript from 'highlight.js/lib/languages/javascript';
import json from 'highlight.js/lib/languages/json';
import php from 'highlight.js/lib/languages/php';
import python from 'highlight.js/lib/languages/python';
import sql from 'highlight.js/lib/languages/sql';
import xml from 'highlight.js/lib/languages/xml';

Object.entries({ bash, css, javascript, json, php, python, sql, xml })
    .forEach(([name, lang]) => hljs.registerLanguage(name, lang));
hljs.registerAliases(['html', 'blade', 'vue'], { languageName: 'xml' });
hljs.registerAliases(['js', 'ts'], { languageName: 'javascript' });
hljs.registerAliases(['sh', 'shell'], { languageName: 'bash' });

export function highlightWithin(root = document) {
    root?.querySelectorAll('.aq-markdown pre code:not(.hljs)').forEach((block) => hljs.highlightElement(block));
}
