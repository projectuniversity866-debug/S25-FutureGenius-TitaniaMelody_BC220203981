<script setup>
/*
 * Formatting toolbar and Write / Preview tabs for an existing <textarea>.
 *
 * Progressive enhancement: the textarea is server-rendered and keeps working
 * without JavaScript. This component adds buttons that wrap the selection in
 * Markdown, keyboard shortcuts (Ctrl+B, Ctrl+I, Ctrl+K), and a preview that
 * the server renders with the same code as the published page.
 */
import { nextTick, onMounted, ref, useId } from 'vue';

const props = defineProps({
    for: { type: String, required: true },
    previewUrl: { type: String, required: true },
});

const id = useId();
const mode = ref('write');
const html = ref('');
const loading = ref(false);
const error = ref('');
const previewEl = ref(null);
let field = null;

const tools = [
    { label: 'Bold', icon: 'type-bold', key: 'b', wrap: ['**', '**'], placeholder: 'bold text' },
    { label: 'Italic', icon: 'type-italic', key: 'i', wrap: ['_', '_'], placeholder: 'italic text' },
    { label: 'Link', icon: 'link-45deg', key: 'k', wrap: ['[', '](https://)'], placeholder: 'link text' },
    { label: 'Inline code', icon: 'code', wrap: ['`', '`'], placeholder: 'code' },
    { label: 'Code block', icon: 'code-square', wrap: ['\n```\n', '\n```\n'], placeholder: 'your code here' },
    { label: 'Quote', icon: 'quote', line: '> ' },
    { label: 'Bulleted list', icon: 'list-ul', line: '- ' },
    { label: 'Numbered list', icon: 'list-ol', line: '1. ' },
];

function apply(tool) {
    const { selectionStart: start, selectionEnd: end, value } = field;
    let selected = value.slice(start, end);
    let insert;

    if (tool.line) {
        const lines = (selected || 'item').split('\n');
        insert = lines.map((l, i) => (tool.line === '1. ' ? `${i + 1}. ` : tool.line) + l).join('\n');
        if (start > 0 && value[start - 1] !== '\n') insert = '\n' + insert;
    } else {
        selected ||= tool.placeholder;
        insert = tool.wrap[0] + selected + tool.wrap[1];
    }

    field.focus();
    field.setRangeText(insert, start, end, 'end');
    if (!value.slice(start, end) && tool.wrap) {
        // Select the placeholder so the user can type over it.
        const from = start + tool.wrap[0].length;
        field.setSelectionRange(from, from + selected.length);
    }
    // Let the character counter and validation see the change.
    field.dispatchEvent(new Event('input', { bubbles: true }));
}

function onShortcut(event) {
    if (!(event.ctrlKey || event.metaKey)) return;
    const tool = tools.find((t) => t.key === event.key.toLowerCase());
    if (tool) {
        event.preventDefault();
        apply(tool);
    }
}

async function show(next) {
    mode.value = next;
    field.hidden = next === 'preview';
    if (next !== 'preview') return field.focus();

    loading.value = true;
    error.value = '';
    try {
        const response = await fetch(props.previewUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ text: field.value }),
        });
        if (!response.ok) throw new Error(String(response.status));
        html.value = (await response.json()).html;
        // Render the preview first, then highlight the code blocks in it.
        loading.value = false;
        await nextTick();
        if (previewEl.value?.querySelector('pre code')) {
            const { highlightWithin } = await import('../highlight.js');
            highlightWithin(previewEl.value);
        }
    } catch {
        error.value = 'The preview could not be loaded. Your text is safe — switch back to Write.';
    } finally {
        loading.value = false;
    }
}

// Arrow keys move between the two tabs (ARIA tabs pattern).
function onTabKey(event) {
    if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
        event.preventDefault();
        const next = mode.value === 'write' ? 'preview' : 'write';
        show(next).then(() => document.getElementById(`${id}-tab-${next}`)?.focus());
    }
}

onMounted(() => {
    field = document.getElementById(props.for);
    field?.addEventListener('keydown', onShortcut);
});
</script>

<template>
    <div class="aq-md-editor">
        <div class="aq-md-bar">
            <div role="tablist" aria-label="Editor mode" class="aq-md-tabs">
                <button v-for="m in ['write', 'preview']" :key="m" type="button" role="tab"
                        :id="`${id}-tab-${m}`" :aria-selected="mode === m" :tabindex="mode === m ? 0 : -1"
                        :aria-controls="m === 'write' ? props.for : `${id}-panel`"
                        @click="show(m)" @keydown="onTabKey">
                    {{ m === 'write' ? 'Write' : 'Preview' }}
                </button>
            </div>
            <div v-show="mode === 'write'" role="toolbar" aria-label="Formatting" class="aq-md-tools">
                <button v-for="tool in tools" :key="tool.label" type="button" class="aq-md-tool"
                        :title="tool.key ? `${tool.label} (Ctrl+${tool.key.toUpperCase()})` : tool.label"
                        :aria-label="tool.label" @click="apply(tool)">
                    <i :class="`bi bi-${tool.icon}`" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <div v-if="mode === 'preview'" :id="`${id}-panel`" role="tabpanel" :aria-labelledby="`${id}-tab-preview`"
             class="aq-md-preview" aria-live="polite" :aria-busy="loading">
            <p v-if="loading" class="aq-muted mb-0">Rendering preview…</p>
            <p v-else-if="error" class="aq-danger mb-0">{{ error }}</p>
            <p v-else-if="!html.trim()" class="aq-muted mb-0">Nothing to preview yet.</p>
            <div v-else ref="previewEl" class="prose aq-markdown" v-html="html"></div>
        </div>
    </div>
</template>
