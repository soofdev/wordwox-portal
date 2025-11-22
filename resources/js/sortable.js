// SortableJS Integration for Drag & Drop Block Reordering
import Sortable from 'sortablejs';

// Global Sortable instances
window.sortableInstances = window.sortableInstances || {};

/**
 * Initialize SortableJS for block reordering
 * @param {string} containerId - The ID of the container element
 * @param {string} wireModel - The Livewire model path for reordering
 * @param {object} options - Additional Sortable options
 */
window.initSortable = function(containerId, wireModel, options = {}) {
    const container = document.querySelector(`#${containerId}`);
    if (!container) {
        console.error(`Sortable container #${containerId} not found`);
        return null;
    }

    // Destroy existing instance if any
    if (window.sortableInstances[containerId]) {
        window.sortableInstances[containerId].destroy();
        delete window.sortableInstances[containerId];
    }

    // Find Livewire component
    const livewireComponent = container.closest('[wire\\:id]');
    if (!livewireComponent) {
        console.warn('Livewire component not found for Sortable');
        return null;
    }

    const componentId = livewireComponent.getAttribute('wire:id');
    const livewire = window.Livewire?.find(componentId);

    if (!livewire) {
        console.warn('Livewire instance not found');
        return null;
    }

    // Default options
    const defaultOptions = {
        animation: 150,
        handle: '.drag-handle', // Use drag handle if available
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag',
        fallbackOnBody: true,
        swapThreshold: 0.65,
        group: 'blocks',
        onEnd: function(evt) {
            // Get all block UUIDs in new order
            const items = Array.from(container.children);
            const newOrder = items.map((item) => {
                // Extract block UUID from data attribute
                const blockUuid = item.getAttribute('data-block-uuid');
                return blockUuid;
            }).filter(uuid => uuid !== null && uuid !== '');

            // Only update if we have valid UUIDs
            if (newOrder.length > 0 && livewire) {
                // Call a Livewire method to reorder blocks by UUID
                livewire.call('reorderBlocksByIndex', newOrder);
            }
        }
    };

    // Merge with custom options
    const config = { ...defaultOptions, ...options };

    // Create Sortable instance
    const sortable = Sortable.create(container, config);

    // Store instance
    window.sortableInstances[containerId] = sortable;

    return sortable;
};

/**
 * Destroy Sortable instance
 * @param {string} containerId - The ID of the container element
 */
window.destroySortable = function(containerId) {
    if (window.sortableInstances && window.sortableInstances[containerId]) {
        window.sortableInstances[containerId].destroy();
        delete window.sortableInstances[containerId];
    }
};

/**
 * Initialize Sortable for blocks container
 * @param {string} containerId - The ID of the blocks container
 */
window.initBlockSortable = function(containerId) {
    return window.initSortable(containerId, 'blocks', {
        handle: '[data-drag-handle]',
        animation: 200,
        ghostClass: 'opacity-50',
        chosenClass: 'ring-2 ring-blue-500',
        dragClass: 'shadow-lg'
    });
};

