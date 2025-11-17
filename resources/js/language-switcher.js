/**
 * Real-time Language Switcher
 * Handles language changes without page refresh
 */

document.addEventListener('DOMContentLoaded', function() {
    // Listen for language change events from Livewire
    document.addEventListener('livewire:init', function () {
        Livewire.on('language-changed', (event) => {
            const { language, locale, isRtl } = event[0];
            
            console.log('Language changed:', { language, locale, isRtl });
            
            // Update HTML attributes for RTL/LTR
            updateHtmlDirection(isRtl, language);
            
            // Load appropriate CSS for RTL languages
            updateLanguageCSS(isRtl);
            
            // Update all Livewire components
            refreshLivewireComponents();
            
            // Show success notification
            showLanguageChangeNotification(language);
        });
    });
});

/**
 * Update HTML direction and language attributes
 */
function updateHtmlDirection(isRtl, language) {
    const html = document.documentElement;
    const body = document.body;
    
    // Set direction
    html.setAttribute('dir', isRtl ? 'rtl' : 'ltr');
    body.setAttribute('dir', isRtl ? 'rtl' : 'ltr');
    
    // Set language
    const langCode = language.split('-')[0];
    html.setAttribute('lang', langCode);
    
    // Add/remove RTL class for CSS targeting
    if (isRtl) {
        html.classList.add('rtl');
        body.classList.add('rtl');
    } else {
        html.classList.remove('rtl');
        body.classList.remove('rtl');
    }
}

/**
 * Dynamically load/unload Arabic CSS for RTL languages
 */
function updateLanguageCSS(isRtl) {
    const existingArabicCSS = document.querySelector('link[href*="arabic"]');
    
    if (isRtl && !existingArabicCSS) {
        // Load Arabic CSS
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = '/build/assets/arabic.css'; // Adjust path as needed
        link.id = 'arabic-css';
        document.head.appendChild(link);
    } else if (!isRtl && existingArabicCSS) {
        // Remove Arabic CSS
        existingArabicCSS.remove();
    }
}

/**
 * Refresh all Livewire components to update translations
 */
function refreshLivewireComponents() {
    // Get all Livewire components on the page
    const livewireComponents = document.querySelectorAll('[wire\\:id]');
    
    livewireComponents.forEach(component => {
        const wireId = component.getAttribute('wire:id');
        if (wireId && window.Livewire) {
            try {
                // Refresh the component to get new translations
                window.Livewire.find(wireId).$refresh();
            } catch (error) {
                console.warn('Could not refresh Livewire component:', wireId, error);
            }
        }
    });
}

/**
 * Show language change notification
 */
function showLanguageChangeNotification(language) {
    const languageNames = {
        'en-US': 'English',
        'en': 'English',
        'ar-SA': 'العربية',
        'ar': 'العربية'
    };
    
    const languageName = languageNames[language] || language;
    
    // Use Flux toast if available
    if (window.$flux && window.$flux.toast) {
        window.$flux.toast(`Language changed to ${languageName}`, { 
            variant: 'success',
            duration: 3000 
        });
    } else {
        // Fallback notification
        console.log(`Language changed to ${languageName}`);
    }
}

/**
 * Alternative: Full page refresh with smooth transition
 */
function smoothPageRefresh() {
    // Add fade out effect
    document.body.style.transition = 'opacity 0.3s ease';
    document.body.style.opacity = '0';
    
    // Refresh after fade out
    setTimeout(() => {
        window.location.reload();
    }, 300);
}

/**
 * Export functions for manual use
 */
window.LanguageSwitcher = {
    updateHtmlDirection,
    updateLanguageCSS,
    refreshLivewireComponents,
    smoothPageRefresh
};
