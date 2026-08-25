/**
 * A minimal Alpine directive for drag-and-drop reordering, using native
 * HTML5 drag events rather than pulling in a library — the builder only
 * needs "pick up a row, drop it before/after a sibling", not touch
 * support or animation.
 *
 * Usage: <div x-sortable="(orderedIds) => reorderSections(orderedIds)">
 *   <div x-sortable-item="section.id"> ... </div>
 * </div>
 */
export default function registerSortable(Alpine) {
    Alpine.directive('sortable', (el, { expression }, { evaluate }) => {
        el.addEventListener('dragover', (event) => {
            event.preventDefault();
            const dragging = el.querySelector('[data-dragging="true"]');
            if (!dragging) return;

            const afterElement = getDragAfterElement(el, event.clientY);
            if (afterElement == null) {
                el.appendChild(dragging);
            } else {
                el.insertBefore(dragging, afterElement);
            }
        });

        el.addEventListener('drop', (event) => {
            event.preventDefault();
            const orderedIds = Array.from(el.querySelectorAll('[data-sortable-id]')).map((node) =>
                JSON.parse(node.dataset.sortableId),
            );
            evaluate(expression, { scope: { $orderedIds: orderedIds } });
        });
    });

    Alpine.directive('sortable-item', (el, { expression }, { evaluate }) => {
        const id = evaluate(expression);
        el.dataset.sortableId = JSON.stringify(id);
        el.setAttribute('draggable', 'true');

        el.addEventListener('dragstart', () => {
            el.dataset.dragging = 'true';
            requestAnimationFrame(() => el.classList.add('opacity-40'));
        });

        el.addEventListener('dragend', () => {
            delete el.dataset.dragging;
            el.classList.remove('opacity-40');
        });
    });
}

function getDragAfterElement(container, y) {
    const items = Array.from(container.querySelectorAll('[data-sortable-id]:not([data-dragging="true"])'));

    return items.reduce(
        (closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;

            if (offset < 0 && offset > closest.offset) {
                return { offset, element: child };
            }

            return closest;
        },
        { offset: Number.NEGATIVE_INFINITY, element: null },
    ).element;
}
