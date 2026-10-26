<script setup>
/*
 * Daily line chart (one or more series) for the dashboards.
 *
 * Marks follow the AQDesk chart rules: 2px lines, end dots with a surface
 * ring, hairline grid, one y-axis. A legend always names the series; end
 * labels are added when they do not collide. Hover shows a crosshair that
 * snaps to the nearest day with one tooltip for every series; keyboard users
 * focus the chart and move day by day with the arrow keys, and each day is
 * announced. Every value is also in the server-rendered table below.
 */
import { computed, onBeforeUnmount, onMounted, ref, useId } from 'vue';

const props = defineProps({
    labels: { type: Array, required: true },   // ISO dates, oldest first
    series: { type: Array, required: true },   // [{ name, values: [] }]
    label: { type: String, required: true },
});

const HEIGHT = 220;
const M = { top: 14, right: 84, bottom: 28, left: 34 };

const id = useId();
const root = ref(null);
const width = ref(640);
const active = ref(null);      // index of the highlighted day
const announcement = ref('');
let observer;

const fmtDay = (iso, long = false) => new Date(`${iso}T00:00:00`).toLocaleDateString('en-GB',
    long ? { weekday: 'short', day: 'numeric', month: 'short' } : { day: 'numeric', month: 'short' });

const innerW = computed(() => Math.max(width.value - M.left - M.right, 120));
const innerH = HEIGHT - M.top - M.bottom;

// Clean y-axis: 0 and 3–4 round steps (1, 2, 5 × 10^n). Counts never get
// fractional ticks such as 0.5.
const scale = computed(() => {
    const values = props.series.flatMap((s) => s.values);
    const max = Math.max(1, ...values);
    const rough = max / 4;
    const pow = 10 ** Math.floor(Math.log10(rough));
    let step = [1, 2, 5, 10].map((m) => m * pow).find((s) => s >= rough) || pow;
    if (values.every(Number.isInteger)) step = Math.max(1, Math.round(step));
    const top = Math.max(step, Math.ceil(max / step) * step);
    const ticks = [];
    for (let v = 0; v <= top + 1e-9; v += step) ticks.push(Math.round(v * 100) / 100);
    return { top, ticks };
});

const x = (i) => M.left + (props.labels.length < 2 ? innerW.value / 2 : (i / (props.labels.length - 1)) * innerW.value);
const y = (v) => M.top + innerH - (v / scale.value.top) * innerH;

const lines = computed(() => props.series.map((s, k) => ({
    name: s.name,
    cls: `aq-series-${k + 1}`,
    points: s.values.map((v, i) => `${x(i).toFixed(1)},${y(v).toFixed(1)}`).join(' '),
    endX: x(s.values.length - 1),
    endY: y(s.values[s.values.length - 1]),
    endValue: s.values[s.values.length - 1],
})));

// End labels only when they cannot overlap; otherwise the legend carries identity.
const showEndLabels = computed(() => {
    const ys = lines.value.map((l) => l.endY).sort((a, b) => a - b);
    return ys.every((v, i) => i === 0 || v - ys[i - 1] >= 16);
});

const xTicks = computed(() => {
    const n = props.labels.length;
    const every = Math.max(1, Math.ceil(n / Math.max(2, Math.floor(innerW.value / 70))));
    return props.labels
        .map((iso, i) => ({ i, text: fmtDay(iso) }))
        .filter(({ i }) => i % every === (n - 1) % every);
});

const tooltip = computed(() => {
    if (active.value === null) return null;
    const i = active.value;
    const left = x(i);
    return {
        day: fmtDay(props.labels[i], true),
        rows: props.series.map((s, k) => ({ name: s.name, value: s.values[i], cls: `aq-series-${k + 1}` })),
        style: { left: `${left}px`, transform: left > width.value * 0.6 ? 'translateX(calc(-100% - 14px))' : 'translateX(14px)' },
    };
});

function nearestIndex(clientX) {
    const box = root.value.getBoundingClientRect();
    const px = clientX - box.left - M.left;
    const n = props.labels.length;
    return Math.min(n - 1, Math.max(0, Math.round((px / innerW.value) * (n - 1))));
}

function onPointerMove(event) { active.value = nearestIndex(event.clientX); }
function onPointerLeave() { if (document.activeElement !== root.value) active.value = null; }

function announce(i) {
    const parts = props.series.map((s) => `${s.name} ${s.values[i]}`).join(', ');
    announcement.value = `${fmtDay(props.labels[i], true)}: ${parts}`;
}

function onKeydown(event) {
    const last = props.labels.length - 1;
    const moves = { ArrowRight: 1, ArrowLeft: -1, Home: -Infinity, End: Infinity };
    if (!(event.key in moves)) return;
    event.preventDefault();
    const from = active.value ?? last;
    active.value = Math.min(last, Math.max(0, moves[event.key] === Infinity ? last
        : moves[event.key] === -Infinity ? 0 : from + moves[event.key]));
    announce(active.value);
}

function onFocus() {
    if (active.value === null) active.value = props.labels.length - 1;
    announce(active.value);
}

onMounted(() => {
    width.value = root.value.clientWidth || width.value;
    observer = new ResizeObserver(([entry]) => { width.value = entry.contentRect.width; });
    observer.observe(root.value);
});
onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <div class="aq-linechart">
        <ul class="aq-legend" :aria-label="`Series in ${label}`">
            <li v-for="line in lines" :key="line.name">
                <span class="aq-legend-key" :class="line.cls" aria-hidden="true"></span>{{ line.name }}
            </li>
        </ul>

        <div ref="root" class="aq-linechart-plot" tabindex="0" role="group"
             :aria-label="`${label}. Use the left and right arrow keys to read each day.`"
             :aria-describedby="`${id}-hint`"
             @pointermove="onPointerMove" @pointerleave="onPointerLeave"
             @keydown="onKeydown" @focus="onFocus" @blur="active = null">
            <svg :width="width" :height="HEIGHT" aria-hidden="true" focusable="false">
                <g class="aq-grid">
                    <line v-for="t in scale.ticks" :key="t" :x1="M.left" :x2="M.left + innerW" :y1="y(t)" :y2="y(t)" />
                </g>
                <g class="aq-axis">
                    <text v-for="t in scale.ticks" :key="`y${t}`" :x="M.left - 8" :y="y(t)" text-anchor="end" dominant-baseline="middle">{{ t }}</text>
                    <text v-for="t in xTicks" :key="`x${t.i}`" :x="x(t.i)" :y="HEIGHT - 8" text-anchor="middle">{{ t.text }}</text>
                </g>

                <line v-if="active !== null" class="aq-crosshair" :x1="x(active)" :x2="x(active)" :y1="M.top" :y2="M.top + innerH" />

                <g v-for="line in lines" :key="line.name" :class="line.cls">
                    <polyline class="aq-line" :points="line.points" />
                    <circle class="aq-dot" :cx="line.endX" :cy="line.endY" r="4" />
                    <circle v-if="active !== null" class="aq-dot"
                            :cx="x(active)" :cy="y(series[lines.indexOf(line)].values[active])" r="4" />
                    <text v-if="showEndLabels" class="aq-end-label" :x="line.endX + 10" :y="line.endY" dominant-baseline="middle">
                        {{ line.name }} <tspan class="aq-end-value">{{ line.endValue }}</tspan>
                    </text>
                </g>
            </svg>

            <div v-if="tooltip" class="aq-tooltip" :style="tooltip.style" aria-hidden="true">
                <p class="aq-tooltip-day">{{ tooltip.day }}</p>
                <p v-for="row in tooltip.rows" :key="row.name" class="aq-tooltip-row">
                    <span class="aq-legend-key" :class="row.cls"></span>
                    <strong>{{ row.value }}</strong>
                    <span>{{ row.name }}</span>
                </p>
            </div>
        </div>

        <span :id="`${id}-hint`" class="visually-hidden">The same figures are in the table below the chart.</span>
        <span class="visually-hidden" role="status" aria-live="polite">{{ announcement }}</span>
    </div>
</template>
