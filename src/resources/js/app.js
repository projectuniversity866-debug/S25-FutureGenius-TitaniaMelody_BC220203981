/*
 * AQDesk front-end entry point — "Vue islands".
 *
 * Pages are rendered by Blade and work without JavaScript. Where a page wants
 * an interactive enhancement it leaves a mount point:
 *
 *     <div data-vue="CharacterCounter" data-props='{"for": "body", "min": 20}'></div>
 *
 * and the matching component from resources/js/components/ is mounted there
 * with those props. See docs/architecture/ARCHITECTURE.md (ADR-02).
 */
import { createApp } from 'vue';
import { initConfirmDialogs, initToasts } from './feedback.js';
import { initLiveNotifications } from './live-notifications.js';
import { initAccessibilityToolbar } from './a11y.js';

const components = import.meta.glob('./components/**/*.vue', { eager: true });

const registry = Object.fromEntries(
    Object.entries(components).map(([path, module]) => [
        path.split('/').pop().replace('.vue', ''),
        module.default,
    ]),
);

function mountIslands(root = document) {
    root.querySelectorAll('[data-vue]').forEach((el, index) => {
        const component = registry[el.dataset.vue];

        if (!component) {
            console.warn(`AQDesk: no Vue component named "${el.dataset.vue}".`);
            return;
        }

        let props = {};
        try {
            props = el.dataset.props ? JSON.parse(el.dataset.props) : {};
        } catch (error) {
            console.warn(`AQDesk: invalid data-props on "${el.dataset.vue}".`, error);
        }

        const app = createApp(component, props);

        // Each island is its own Vue app, so ids from useId() would repeat
        // ("v-0" in every island). A per-island prefix keeps them unique,
        // which matters for aria-describedby and label associations.
        app.config.idPrefix = `aq${index}`;

        app.mount(el);
    });
}

/*
 * Responsive tables (class "aq-table") are restyled as cards on phones with
 * display:block, which makes some browsers stop exposing them as tables to
 * screen readers. Explicit roles keep rows, headers and cells announced.
 */
function restoreTableSemantics(root = document) {
    root.querySelectorAll('table.aq-table').forEach((table) => {
        table.setAttribute('role', 'table');
        table.querySelectorAll('thead, tbody, tfoot').forEach((group) => group.setAttribute('role', 'rowgroup'));
        table.querySelectorAll('tr').forEach((row) => row.setAttribute('role', 'row'));
        table.querySelectorAll('th[scope="col"]').forEach((th) => th.setAttribute('role', 'columnheader'));
        table.querySelectorAll('th[scope="row"]').forEach((th) => th.setAttribute('role', 'rowheader'));
        table.querySelectorAll('td').forEach((td) => td.setAttribute('role', 'cell'));
    });
}

mountIslands();
restoreTableSemantics();
initConfirmDialogs();
initToasts();
initLiveNotifications();
initAccessibilityToolbar();

// The syntax highlighter is only downloaded on pages that show code.
if (document.querySelector('.aq-markdown pre code')) {
    import('./highlight.js').then(({ highlightWithin }) => highlightWithin());
}
