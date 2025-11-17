{{-- Flux UI Language Switcher --}}
@php
$currentLanguage = session('public_locale', 'en');
$availableLanguages = [
'en' => ['name' => 'English', 'native' => 'English', 'flag' => '🇺🇸', 'rtl' => false],
'ar' => ['name' => 'Arabic', 'native' => 'العربية', 'flag' => '🇸🇦', 'rtl' => true],
];
@endphp

<flux:dropdown position="bottom" align="end">
    {{-- Language Switcher Button --}}
    <flux:button variant="outline" size="sm" class="text-sm font-medium text-gray-700">
        <span class="text-lg mr-2 rtl:ml-2 rtl:mr-0">{{ $availableLanguages[$currentLanguage]['flag'] }}</span>
        <span class="hidden sm:block">{{ $availableLanguages[$currentLanguage]['native'] }}</span>
        <span class="block sm:hidden">{{ strtoupper($currentLanguage) }}</span>
        <flux:icon name="chevron-down" class="ml-2 rtl:mr-2 rtl:ml-0 h-4 w-4" />
    </flux:button>

    {{-- Navigation Menu --}}
    <flux:navmenu>
        @foreach($availableLanguages as $langCode => $langData)
        <flux:navmenu.item href="#" onclick="switchLanguage('{{ $langCode }}'); return false;" class="{{ $currentLanguage === $langCode ? 'bg-blue-50 text-blue-700' : '' }}">
            <div class="flex items-center w-full">
                <span class="text-lg mr-3 rtl:ml-3 rtl:mr-0">{{ $langData['flag'] }}</span>
                <div class="flex flex-col items-start flex-1">
                    <span class="font-medium">{{ $langData['native'] }}</span>
                    <span class="text-xs text-gray-500">{{ $langData['name'] }}</span>
                </div>
                @if($currentLanguage === $langCode)
                <flux:icon name="check" class="h-4 w-4 text-blue-600" />
                @endif
            </div>
        </flux:navmenu.item>
        @endforeach

        {{-- Info Footer --}}
        <div class="px-4 py-2 border-t border-gray-100 bg-gray-50 text-xs text-gray-500 text-center">
            {{ __('public_registration.language_switcher.choose_language') }}
        </div>
    </flux:navmenu>
</flux:dropdown>

{{-- Simple Language Switching JavaScript --}}
@push('scripts')
<script>
    window.switchLanguage = function(langCode) {
        var availableLanguages = @json($availableLanguages);

        if (!availableLanguages[langCode]) {
            console.error('Invalid language code:', langCode);
            return;
        }

        // Get CSRF token
        var csrfToken = '';
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta) {
            csrfToken = csrfMeta.getAttribute('content');
        }

        // Send request to update session and reload
        fetch(window.location.href, {
                method: 'POST'
                , headers: {
                    'Content-Type': 'application/json'
                    , 'X-CSRF-TOKEN': csrfToken
                    , 'X-Requested-With': 'XMLHttpRequest'
                }
                , body: JSON.stringify({
                    action: 'switch_language'
                    , language: langCode
                })
            })
            .then(function(response) {
                if (response.ok) {
                    // Reload page to apply language changes
                    window.location.reload();
                } else {
                    console.error('Failed to switch language');
                }
            })
            .catch(function(error) {
                console.error('Error switching language:', error);
            });
    };

</script>
@endpush
