<script setup>
/*
 * Follow / unfollow a tag without a page reload (roadmap 1.11). Replaces the
 * plain form in community::partials.follow-button.
 *
 * The visible word changes ("Follow" / "Following"), so this is not an
 * aria-pressed toggle; instead the accessible name always contains the
 * visible word and says what pressing does.
 */
import { computed, ref } from 'vue';
import { showToast } from '../feedback.js';
import { sendJson } from '../http.js';

const props = defineProps({
    url: { type: String, required: true },
    tag: { type: String, required: true },
    following: { type: Boolean, default: false },
    count: { type: Number, default: 0 },
});

const following = ref(props.following);
const count = ref(props.count);
const busy = ref(false);

const label = computed(() => (following.value ? `Following ${props.tag}, select to unfollow` : `Follow ${props.tag}`));
const followers = computed(() => `${count.value} ${count.value === 1 ? 'follower' : 'followers'}`);

async function toggle() {
    if (busy.value) return;
    const before = { following: following.value, count: count.value };

    following.value = !following.value;
    count.value += following.value ? 1 : -1;
    busy.value = true;

    const { ok, data, message } = await sendJson(props.url, { method: before.following ? 'DELETE' : 'POST' });
    busy.value = false;

    if (!ok) {
        following.value = before.following;
        count.value = before.count;
        showToast(message, 'error');
        return;
    }

    following.value = data.following;
    count.value = data.count;
    showToast(data.message);
}
</script>

<template>
    <button type="button" class="btn btn-sm" :class="following ? 'btn-soft' : 'btn-quiet'"
            :aria-label="label" :aria-disabled="busy ? 'true' : null" @click="toggle">
        <i :class="following ? 'bi bi-check-lg me-1' : 'bi bi-plus-lg me-1'" aria-hidden="true"></i>{{ following ? 'Following' : 'Follow' }}
    </button>
    <span class="aq-follow-count">{{ followers }}</span>
</template>
