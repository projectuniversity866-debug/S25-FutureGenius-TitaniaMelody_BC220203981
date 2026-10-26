<script setup>
/*
 * Live character count for a text field with a minimum and/or maximum length.
 *
 * Progressive enhancement: the field's static help text ("At least 20
 * characters.") stays in the page for users without JavaScript. Screen
 * readers hear the count when they focus the field (aria-describedby) and a
 * short announcement only when the state changes — never on every keystroke.
 */
import { computed, onBeforeUnmount, onMounted, ref, useId, watch } from 'vue';

const props = defineProps({
    for: { type: String, required: true },
    min: { type: Number, default: 0 },
    max: { type: Number, default: null },
});

const id = useId();
const count = ref(0);
const announcement = ref('');
let field = null;

const update = () => {
    count.value = field.value.length;
};

const state = computed(() => {
    if (props.max && count.value > props.max) return 'over';
    if (count.value < props.min) return 'short';
    if (props.max && count.value >= props.max * 0.9) return 'near';
    return 'ok';
});

const visual = computed(() => {
    if (state.value === 'short') {
        return `${count.value} / ${props.min} · ${props.min - count.value} more needed`;
    }
    return props.max ? `${count.value} / ${props.max}` : `${count.value} characters`;
});

const summary = computed(() => {
    switch (state.value) {
        case 'short':
            return `${count.value} characters. At least ${props.min} needed, ${props.min - count.value} more to go.`;
        case 'over':
            return `${count.value} characters. ${count.value - props.max} over the limit of ${props.max}.`;
        default:
            return props.max
                ? `${count.value} of ${props.max} characters used.`
                : `${count.value} characters.`;
    }
});

watch(state, (now, before) => {
    if (before === undefined) return;
    announcement.value = {
        short: `Below the minimum of ${props.min} characters.`,
        ok: 'Length is fine.',
        near: `Nearly at the limit of ${props.max} characters.`,
        over: `Over the limit of ${props.max} characters.`,
    }[now];
});

onMounted(() => {
    field = document.getElementById(props.for);
    if (!field) return;

    const describedBy = (field.getAttribute('aria-describedby') || '').split(' ').filter(Boolean);
    field.setAttribute('aria-describedby', [...describedBy, id].join(' '));

    field.addEventListener('input', update);
    update();
});

onBeforeUnmount(() => field?.removeEventListener('input', update));
</script>

<template>
    <p :id="id" class="aq-char-counter" :class="`is-${state}`">
        <span aria-hidden="true">{{ visual }}</span>
        <span class="visually-hidden">{{ summary }}</span>
    </p>
    <span class="visually-hidden" role="status" aria-live="polite">{{ announcement }}</span>
</template>
