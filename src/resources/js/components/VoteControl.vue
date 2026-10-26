<script setup>
/*
 * Up / down vote buttons that work without a page reload (roadmap 1.11).
 *
 * Progressive enhancement: the server renders two small forms in this spot
 * (community::partials.vote), which work without JavaScript. This island
 * replaces them and sends the vote as JSON to the same route. The new score
 * shows at once; if the server refuses (your own post, downvoting without
 * enough reputation) the old score comes back and a message says why.
 */
import { ref } from 'vue';
import { announce, showToast } from '../feedback.js';
import { sendJson } from '../http.js';

const props = defineProps({
    url: { type: String, required: true },
    score: { type: Number, required: true },
    vote: { type: Number, default: null }, // 1, -1 or null
    noun: { type: String, default: 'post' }, // "question" or "answer"
});

const score = ref(props.score);
const vote = ref(props.vote);
const busy = ref(false);

const votes = (n) => `${n} ${Math.abs(n) === 1 ? 'vote' : 'votes'}`;

async function cast(value) {
    if (busy.value) return;
    const before = { score: score.value, vote: vote.value };

    // Voting the same way again removes the vote; the other way switches it.
    const removing = vote.value === value;
    score.value += removing ? -value : value - (vote.value ?? 0);
    vote.value = removing ? null : value;
    busy.value = true;

    const { ok, data, message } = await sendJson(props.url, { body: { value } });
    busy.value = false;

    if (!ok) {
        score.value = before.score;
        vote.value = before.vote;
        showToast(message, 'error');
        return;
    }

    score.value = data.score;
    vote.value = data.vote;
    announce(vote.value === null
        ? `Vote removed. ${votes(score.value)}.`
        : `${vote.value > 0 ? 'Upvoted' : 'Downvoted'}. ${votes(score.value)}.`);
}
</script>

<template>
    <button type="button" class="vote-btn" :class="{ 'is-active': vote === 1 }"
            :aria-label="`Upvote this ${noun}`" :aria-pressed="vote === 1 ? 'true' : 'false'"
            :aria-disabled="busy ? 'true' : null" @click="cast(1)">
        <i class="bi bi-caret-up-fill" aria-hidden="true"></i>
    </button>

    <div class="aq-vote-score">{{ score }}<span class="visually-hidden"> {{ Math.abs(score) === 1 ? 'vote' : 'votes' }}</span></div>

    <button type="button" class="vote-btn" :class="{ 'is-active': vote === -1 }"
            :aria-label="`Downvote this ${noun}`" :aria-pressed="vote === -1 ? 'true' : 'false'"
            :aria-disabled="busy ? 'true' : null" @click="cast(-1)">
        <i class="bi bi-caret-down-fill" aria-hidden="true"></i>
    </button>
</template>
