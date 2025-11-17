<!-- Flash Messages Component using Flux Toast -->
@persist('toast')
    <flux:toast position="top-right" />
@endpersist

@if (session()->has('success'))
    <div
        x-data="{
            init() {
                console.log('Success Toast:', '{{ addslashes(session('success')) }}'); // Debugging toast message
                $flux.toast('{{ addslashes(session('success')) }}', { variant: 'success' });
            }
        }"
    ></div>
@endif

@if (session()->has('error'))
    <div
        x-data="{
            init() {
                console.log('Error Toast:', '{{ addslashes(session('error')) }}'); // Debugging toast message
                $flux.toast('{{ addslashes(session('error')) }}', { variant: 'danger' });
            }
        }"
    ></div>
@endif

@if (session()->has('warning'))
    <div
        x-data="{
            init() {
                $flux.toast('{{ addslashes(session('warning')) }}', { variant: 'warning' });
            }
        }"
    ></div>
@endif

@if (session()->has('info'))
    <div
        x-data="{
            init() {
                $flux.toast('{{ addslashes(session('info')) }}', { variant: 'info' });
            }
        }"
    ></div>
@endif