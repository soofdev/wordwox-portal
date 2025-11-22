// Filepond Integration for Image Uploads
import * as FilePond from 'filepond';
import 'filepond/dist/filepond.min.css';

// Register FilePond plugins if needed
// import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
// import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css';
// FilePond.registerPlugin(FilePondPluginImagePreview);

// Global FilePond instances
window.filepondInstances = window.filepondInstances || {};

/**
 * Initialize FilePond for an image upload field
 * @param {string} inputId - The ID of the input element
 * @param {string} uploadUrl - The upload endpoint URL
 * @param {object} options - Additional FilePond options
 */
window.initFilePond = function(inputId, uploadUrl, options = {}) {
    const input = document.querySelector(`#${inputId}`);
    if (!input) {
        console.error(`FilePond input #${inputId} not found`);
        return null;
    }

    // Destroy existing instance if any
    if (window.filepondInstances[inputId]) {
        window.filepondInstances[inputId].destroy();
        delete window.filepondInstances[inputId];
    }

    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Default options
    const defaultOptions = {
        server: {
            url: uploadUrl,
            process: {
                url: '/cms-admin/upload-image',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                onload: (response) => {
                    try {
                        const data = JSON.parse(response);
                        return data.url;
                    } catch (e) {
                        return response;
                    }
                }
            },
            revert: null,
            restore: null,
            load: null
        },
        allowMultiple: false,
        maxFiles: 1,
        acceptedFileTypes: ['image/*'],
        maxFileSize: '10MB',
        imagePreviewHeight: 200,
        imageCropAspectRatio: null,
        imageResizeTargetWidth: null,
        imageResizeTargetHeight: null,
        stylePanelLayout: 'integrated',
        styleButtonRemoveItemPosition: 'right',
        styleLoadIndicatorPosition: 'right bottom',
        styleButtonProcessItemPosition: 'right bottom'
    };

    // Merge with custom options
    const config = { ...defaultOptions, ...options };

    // Create FilePond instance
    const pond = FilePond.create(input, config);

    // Store instance
    window.filepondInstances[inputId] = pond;

    return pond;
};

/**
 * Destroy FilePond instance
 * @param {string} inputId - The ID of the input element
 */
window.destroyFilePond = function(inputId) {
    if (window.filepondInstances && window.filepondInstances[inputId]) {
        window.filepondInstances[inputId].destroy();
        delete window.filepondInstances[inputId];
    }
};

/**
 * Initialize FilePond for multiple images (gallery)
 * @param {string} inputId - The ID of the input element
 * @param {string} uploadUrl - The upload endpoint URL
 */
window.initFilePondGallery = function(inputId, uploadUrl) {
    return window.initFilePond(inputId, uploadUrl, {
        allowMultiple: true,
        maxFiles: 20
    });
};

