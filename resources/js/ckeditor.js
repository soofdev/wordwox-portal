// CKEditor 5 Integration with Livewire and Language Support
// Using CDN version for better compatibility and Arabic support

// Global CKEditor instances storage
window.ckeditorInstances = window.ckeditorInstances || {};

// Load CKEditor 5 from CDN
const loadCKEditor = () => {
    return new Promise((resolve, reject) => {
        if (window.ClassicEditor) {
            console.log('CKEditor already loaded');
            resolve(window.ClassicEditor);
            return;
        }

        console.log('Loading CKEditor from CDN...');

        // Check if CSS is already loaded
        let cssLoaded = document.querySelector('link[href*="ckeditor"]');
        if (!cssLoaded) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://cdn.ckeditor.com/ckeditor5/41.2.1/classic/ckeditor.css';
            document.head.appendChild(link);
            console.log('CKEditor CSS loaded');
        }

        // Check if script is already loaded
        let scriptLoaded = document.querySelector('script[src*="ckeditor"]');
        if (scriptLoaded) {
            // Wait a bit for it to load
            setTimeout(() => {
                if (window.ClassicEditor) {
                    resolve(window.ClassicEditor);
                } else {
                    reject(new Error('CKEditor script loaded but ClassicEditor not available'));
                }
            }, 100);
            return;
        }

        // Load JS
        const script = document.createElement('script');
        script.src = 'https://cdn.ckeditor.com/ckeditor5/41.2.1/classic/ckeditor.js';
        script.onload = () => {
            console.log('CKEditor JS loaded');
            if (window.ClassicEditor) {
                resolve(window.ClassicEditor);
            } else {
                reject(new Error('CKEditor script loaded but ClassicEditor not available'));
            }
        };
        script.onerror = (error) => {
            console.error('Failed to load CKEditor:', error);
            reject(new Error('Failed to load CKEditor from CDN'));
        };
        document.head.appendChild(script);
    });
};

/**
 * Initialize CKEditor 5 for a Livewire component
 * @param {string} elementId - The ID of the textarea element
 * @param {string} wireModel - The Livewire model path (e.g., 'blocks.0.content')
 * @param {string} initialContent - Initial content to set
 * @param {string} language - Language code (en, ar) - defaults to page language
 */
window.initCKEditor = async function(elementId, wireModel, initialContent = '', language = null) {
    const element = document.querySelector(`#${elementId}`);
    if (!element) {
        console.error(`Element #${elementId} not found`);
        return Promise.resolve();
    }

    // Get language from page if not provided
    if (!language) {
        language = document.documentElement.lang || 'en';
    }

    // Destroy existing instance if any
    if (window.ckeditorInstances[elementId]) {
        try {
            await window.ckeditorInstances[elementId].destroy();
        } catch (error) {
            console.error('Error destroying CKEditor:', error);
        }
        delete window.ckeditorInstances[elementId];
    }

    try {
        console.log(`Initializing CKEditor for element: #${elementId}`);
        
        // Load CKEditor from CDN
        const ClassicEditor = await loadCKEditor();
        
        if (!ClassicEditor) {
            throw new Error('ClassicEditor not available');
        }

        console.log('Creating CKEditor instance...');

        // Create editor instance with Classic build features only
        const editor = await ClassicEditor.create(element, {
            language: language,
            toolbar: {
                items: [
                    'heading',
                    '|',
                    'bold',
                    'italic',
                    'underline',
                    '|',
                    'bulletedList',
                    'numberedList',
                    '|',
                    'link',
                    'blockQuote',
                    'insertTable',
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
                    { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
                    { model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' }
                ]
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
            table: {
                contentToolbar: [
                    'tableColumn',
                    'tableRow',
                    'mergeTableCells'
                ]
            }
        });

        // Set initial content
        if (initialContent) {
            editor.setData(initialContent);
        }

        // Find Livewire component and sync
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
        
        console.log(`CKEditor initialized successfully for: #${elementId}`);

        return editor;
    } catch (error) {
        console.error('CKEditor initialization error:', error);
        console.error('Error details:', {
            elementId,
            element: element ? 'found' : 'not found',
            wireModel,
            language
        });
        // Don't reject, just log the error so the page doesn't break
        return null;
    }
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
