<script setup>
/*
 * Save-for-later toggle for a question, without a page reload (roadmap
 * 1.11). Replaces the plain form in community::partials.bookmark.
 *
 * A toggle button: its name stays "Save this question" and aria-pressed
 * tells whether it is saved. POST saves and DELETE removes, on the same URL.
 */
import { ref } from 'vue';
import { showToast } from '../feedback.js';
import { sendJson } from '../http.js';

const props = defineProps({
    url: { type: String, required: true },
    saved: { type: Boolean, default: false },
    count: { type: Number, default: 0 },
});

const saved = ref(props.saved);
const count = ref(props.count);
const busy = ref(false);

async function toggle() {
    if (busy.value) return;
    const before = { saved: saved.value, count: count.value };

    saved.value = !saved.value;
    count.value += saved.value ? 1 : -1;
    busy.value = true;

    const { ok, data, message } = await sendJson(props.url, { method: before.saved ? 'DELETE' : 'POST' });
    busy.value = false;

    if (!ok) {
        saved.value = before.saved;
        count.value = before.count;
        showToast(message, 'error');
        return;
    }

    saved.value = data.saved;
    count.value = data.count;
    showToast(data.message);
}
</script>

<template>
    <button type="button" class="aq-bookmark-btn" :class="{ 'is-active': saved }"
            aria-label="Save this question" :aria-pressed="saved ? 'true' : 'false'" title="Save this question"
            :aria-disabled="busy ? 'true' : null" @click="toggle">
        <i :class="saved ? 'bi bi-bookmark-fill' : 'bi bi-bookmark'" aria-hidden="true"></i>
    </button>
    <span v-if="count > 0" class="aq-bookmark-count">{{ count }}<span class="visually-hidden"> {{ count === 1 ? 'person has' : 'people have' }} saved this</span></span>
</template>
