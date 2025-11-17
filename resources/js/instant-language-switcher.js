/**
 * Instant Language Switcher - No Page Refresh Required
 * This provides immediate language switching with visual feedback
 */

class InstantLanguageSwitcher {
    constructor() {
        this.currentLanguage = document.documentElement.lang || 'en';
        this.translations = {};
        this.init();
    }

    init() {
        // Listen for Livewire language change events
        document.addEventListener('livewire:init', () => {
            Livewire.on('language-changed', (event) => {
                this.handleLanguageChange(event[0]);
            });
        });
    }

    async handleLanguageChange({ language, locale, isRtl }) {
        console.log('Instant language change to:', language);

        // 1. Show immediate visual feedback
        this.showChangeIndicator(language);

        // 2. Update HTML attributes instantly
        this.updateHtmlAttributes(locale, isRtl);

        // 3. Load new translations
        await this.loadTranslations(locale);

        // 4. Update all text content
        this.updateTextContent();

        // 5. Update CSS for RTL/LTR
        this.updateCSS(isRtl);

        // 6. Refresh Livewire components
        this.refreshLivewireComponents();

        // 7. Show success notification
        this.showSuccessNotification(language);
    }

    showChangeIndicator(language) {
        const indicator = document.createElement('div');
        indicator.innerHTML = `
            <div class="fixed top-4 right-4 bg-blue-600 text-white px-4 py-2 rounded-lg shadow-lg z-50 flex items-center space-x-2">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                <span>Changing language...</span>
            </div>
        `;
        document.body.appendChild(indicator);

        // Remove after 2 seconds
        setTimeout(() => {
            indicator.remove();
        }, 2000);
    }

    updateHtmlAttributes(locale, isRtl) {
        const html = document.documentElement;
        const body = document.body;

        // Update language and direction
        html.setAttribute('lang', locale);
        html.setAttribute('dir', isRtl ? 'rtl' : 'ltr');
        body.setAttribute('dir', isRtl ? 'rtl' : 'ltr');

        // Update CSS classes
        html.classList.toggle('rtl', isRtl);
        body.classList.toggle('rtl', isRtl);

        this.currentLanguage = locale;
    }

    async loadTranslations(locale) {
        try {
            // In a real implementation, you might load translations from an API
            // For now, we'll use the existing Laravel translations
            const response = await fetch(`/api/translations/${locale}`);
            if (response.ok) {
                this.translations = await response.json();
            }
        } catch (error) {
            console.warn('Could not load translations:', error);
        }
    }

    updateTextContent() {
        // Update common translatable elements
        const translatableElements = document.querySelectorAll('[data-translate]');
        
        translatableElements.forEach(element => {
            const key = element.getAttribute('data-translate');
            if (this.translations[key]) {
                element.textContent = this.translations[key];
            }
        });

        // Update placeholders
        const inputElements = document.querySelectorAll('input[data-translate-placeholder]');
        inputElements.forEach(input => {
            const key = input.getAttribute('data-translate-placeholder');
            if (this.translations[key]) {
                input.setAttribute('placeholder', this.translations[key]);
            }
        });
    }

    updateCSS(isRtl) {
        // Dynamically load/unload RTL CSS
        const existingRtlCSS = document.getElementById('rtl-css');
        
        if (isRtl && !existingRtlCSS) {
            const link = document.createElement('link');
            link.id = 'rtl-css';
            link.rel = 'stylesheet';
            link.href = '/build/assets/arabic.css';
            document.head.appendChild(link);
        } else if (!isRtl && existingRtlCSS) {
            existingRtlCSS.remove();
        }
    }

    refreshLivewireComponents() {
        // Refresh all Livewire components to get new translations
        const components = document.querySelectorAll('[wire\\:id]');
        
        components.forEach(component => {
            const wireId = component.getAttribute('wire:id');
            if (wireId && window.Livewire) {
                try {
                    window.Livewire.find(wireId).$refresh();
                } catch (error) {
                    console.warn('Could not refresh component:', wireId);
                }
            }
        });
    }

    showSuccessNotification(language) {
        const languageNames = {
            'en-US': 'English',
            'en': 'English', 
            'ar-SA': 'العربية',
            'ar': 'العربية'
        };

        const langName = languageNames[language] || language;

        if (window.$flux && window.$flux.toast) {
            window.$flux.toast(`Language changed to ${langName}`, {
                variant: 'success',
                duration: 3000
            });
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.instantLanguageSwitcher = new InstantLanguageSwitcher();
});

// Export for manual use
window.InstantLanguageSwitcher = InstantLanguageSwitcher;
