import { ClassicEditor, Essentials, Paragraph, Heading, Bold, Italic, Link, List, BlockQuote, Table, TableToolbar, Undo, Alignment } from 'ckeditor5';
import { SimpleUploadAdapter } from '@ckeditor/ckeditor5-upload';

// Global CKEditor instances storage
window.ckeditorInstances = window.ckeditorInstances || {};

/**
 * Initialize CKEditor 5 for a Livewire component with custom features
 * @param {string} elementId - The ID of the textarea element
 * @param {string} wireModel - The Livewire model path (e.g., 'blocks.0.content')
 * @param {string} initialContent - Initial content to set
 * @param {string} language - Language code (en, ar)
 */
window.initCKEditor = function(elementId, wireModel, initialContent = '', language = 'en') {
    const element = document.querySelector(`#${elementId}`);
    if (!element) {
        console.error(`Element #${elementId} not found`);
        return Promise.resolve();
    }

    // Destroy existing instance if any
    if (window.ckeditorInstances[elementId]) {
        return window.ckeditorInstances[elementId].destroy()
            .then(() => {
                delete window.ckeditorInstances[elementId];
            })
            .then(() => initEditor());
    }

    function initEditor() {
        return ClassicEditor
            .create(element, {
                language: language,
                plugins: [
                    Essentials,
                    Paragraph,
                    Heading,
                    Bold,
                    Italic,
                    Link,
                    List,
                    BlockQuote,
                    Table,
                    TableToolbar,
                    Undo,
                    Alignment,
                    SimpleUploadAdapter
                ],
                toolbar: {
                    items: [
                        'heading',
                        '|',
                        'bold',
                        'italic',
                        'link',
                        '|',
                        'alignment',
                        '|',
                        'bulletedList',
                        'numberedList',
                        '|',
                        'blockQuote',
                        'insertTable',
                        'imageUpload',
                        '|',
                        'undo',
                        'redo'
                    ],
                    shouldNotGroupWhenFull: true
                },
                heading: {
                    options: [
                        { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                        { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                        { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                        { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' }
                    ]
                },
                alignment: {
                    options: ['left', 'center', 'right', 'justify']
                },
                link: {
                    decorators: {
                        openInNewTab: {
                            mode: 'manual',
                            label: 'Open in a new tab',
                            attributes: {
                                target: '_blank',
                                rel: 'noopener noreferrer'
                            }
                        }
                    }
                },
                simpleUpload: {
                    uploadUrl: '/cms-admin/upload-image',
                    withCredentials: true,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                },
                table: {
                    contentToolbar: [
                        'tableColumn',
                        'tableRow',
                        'mergeTableCells',
                        'alignment'
                    ]
                }
            })
            .then(editor => {
                // Set initial content
                if (initialContent) {
                    editor.setData(initialContent);
                }

                // Find Livewire component
                const livewireComponent = element.closest('[wire\\:id]');
                if (livewireComponent) {
                    const componentId = livewireComponent.getAttribute('wire:id');
                    const livewire = window.Livewire?.find(componentId);

                    if (livewire && wireModel) {
                        // Sync with Livewire on change (debounced)
                        let timeout;
                        editor.model.document.on('change:data', () => {
                            clearTimeout(timeout);
                            timeout = setTimeout(() => {
                                const data = editor.getData();
                                // Update Livewire property
                                livewire.set(wireModel, data, false);
                            }, 300); // Debounce for 300ms
                        });
                    }
                }

                // Store editor instance
                window.ckeditorInstances[elementId] = editor;

                return editor;
            })
            .catch(error => {
                console.error('CKEditor initialization error:', error);
            });
    }

    return initEditor();
};

/**
 * Destroy CKEditor instance
 */
window.destroyCKEditor = function(elementId) {
    if (window.ckeditorInstances && window.ckeditorInstances[elementId]) {
        return window.ckeditorInstances[elementId].destroy()
            .then(() => {
                delete window.ckeditorInstances[elementId];
            })
            .catch(error => {
                console.error('CKEditor destruction error:', error);
            });
    }
    return Promise.resolve();
};

/**
 * Update CKEditor content
 */
window.updateCKEditor = function(elementId, content) {
    if (window.ckeditorInstances && window.ckeditorInstances[elementId]) {
        window.ckeditorInstances[elementId].setData(content || '');
    }
};

