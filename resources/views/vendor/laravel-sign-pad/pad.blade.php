<x-layouts.app>
    <div class="min-h-screen py-8">
        <div class="max-w-4xl mx-auto px-4">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100 mb-2">
                    ✍️ Digital Signature Required
                </h1>
                <p class="text-lg text-slate-600 dark:text-slate-400">
                    You have agreed to the terms. Please provide your digital signature below.
                </p>

                <!-- Terms Agreed Confirmation -->
                <div class="mt-4 inline-flex items-center px-4 py-2 bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-200 rounded-lg text-sm">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    Terms of Service Agreed
                </div>
            </div>

            <!-- Signature Form -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 p-8">
                <form action="{{ route('sign-pad::signature')}}" method="POST">
                    @csrf
                    <!-- Hidden fields for model identification -->
                    <input type="hidden" name="model" value="{{ request('model') }}">
                    <input type="hidden" name="id" value="{{ request('id') }}">
                    <input type="hidden" name="token" value="{{ request('token') }}">
                    <div class="text-center">
                        <div class="mb-6">
                            <label class="block text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">
                                Sign Here:
                            </label>
                        </div>

                        <x-laravel-sign-pad::signature-pad width="700" height="300" border-color="#cbd5e1" pad-classes="signature-canvas" button-classes="px-6 py-3 text-lg font-semibold rounded-lg transition-colors mx-2" clear-name="🗑️ Clear" submit-name="✅ Complete Signature" :disabled-without-signature="true" />

                        <div class="mt-6 text-sm text-slate-600 dark:text-slate-400">
                            <p>💡 <strong>Tip:</strong> Use your mouse to draw your signature on the canvas above</p>
                            <p>🖱️ Click and drag to create your signature</p>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Custom Styles for Signature Pad -->
    <style>
        .signature-canvas {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .sign-pad-button-clear {
            background: #f1f5f9;
            color: #475569;
            border: 2px solid #cbd5e1;
        }

        .sign-pad-button-clear:hover {
            background: #e2e8f0;
            border-color: #94a3b8;
        }

        .sign-pad-button-submit {
            background: #3b82f6;
            color: white;
            border: 2px solid #3b82f6;
        }

        .sign-pad-button-submit:hover:not(:disabled) {
            background: #2563eb;
            border-color: #2563eb;
        }

        .sign-pad-button-submit:disabled {
            background: #94a3b8;
            border-color: #94a3b8;
            cursor: not-allowed;
            opacity: 0.6;
        }

    </style>
</x-layouts.app>
