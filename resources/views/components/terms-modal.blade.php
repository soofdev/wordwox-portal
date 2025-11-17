<!-- Terms of Service Modal -->
<div id="terms-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-2xl w-full max-h-[80vh] overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">Terms of Service</h2>
            <button onclick="closeTermsModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Content -->
        <div class="px-6 py-4 overflow-y-auto max-h-[60vh] text-slate-700 dark:text-slate-300">
            <div class="space-y-4">
                <h3 class="font-semibold text-lg text-slate-900 dark:text-slate-100">Gym Membership Terms</h3>

                <p>By signing up for membership at our gym, you agree to the following terms:</p>

                <h4 class="font-medium text-slate-900 dark:text-slate-100">1. Membership Rules</h4>
                <ul class="list-disc list-inside space-y-1 ml-4">
                    <li>Members must be at least 16 years old or accompanied by a parent/guardian</li>
                    <li>Valid ID required for access to gym facilities</li>
                    <li>Members are responsible for their personal belongings</li>
                    <li>Proper gym attire and closed-toe shoes required</li>
                </ul>

                <h4 class="font-medium text-slate-900 dark:text-slate-100">2. Safety and Conduct</h4>
                <ul class="list-disc list-inside space-y-1 ml-4">
                    <li>Follow all posted safety guidelines and equipment instructions</li>
                    <li>Report any injuries or incidents to staff immediately</li>
                    <li>Respect other members and maintain appropriate behavior</li>
                    <li>No harassment, discrimination, or inappropriate conduct</li>
                </ul>

                <h4 class="font-medium text-slate-900 dark:text-slate-100">3. Liability</h4>
                <p>Members participate in activities at their own risk. The gym is not liable for injuries, accidents, or loss of personal property.</p>

                <h4 class="font-medium text-slate-900 dark:text-slate-100">4. Privacy</h4>
                <p>We collect and use personal information in accordance with our Privacy Policy. Your data is protected and used only for membership management and communication.</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex justify-end">
            <button onclick="closeTermsModal()" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                I Understand
            </button>
        </div>
    </div>
</div>

<!-- Privacy Policy Modal -->
<div id="privacy-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-2xl w-full max-h-[80vh] overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">Privacy Policy</h2>
            <button onclick="closePrivacyModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Content -->
        <div class="px-6 py-4 overflow-y-auto max-h-[60vh] text-slate-700 dark:text-slate-300">
            <div class="space-y-4">
                <h3 class="font-semibold text-lg text-slate-900 dark:text-slate-100">Information We Collect</h3>

                <h4 class="font-medium text-slate-900 dark:text-slate-100">Personal Information</h4>
                <ul class="list-disc list-inside space-y-1 ml-4">
                    <li>Name, phone number, and email address</li>
                    <li>Emergency contact information</li>
                    <li>Digital signature for agreement verification</li>
                    <li>Gym access and usage data</li>
                </ul>

                <h4 class="font-medium text-slate-900 dark:text-slate-100">How We Use Your Information</h4>
                <ul class="list-disc list-inside space-y-1 ml-4">
                    <li>Membership management and account administration</li>
                    <li>Emergency contact and safety purposes</li>
                    <li>Communication about gym services and updates</li>
                    <li>Legal compliance and record keeping</li>
                </ul>

                <h4 class="font-medium text-slate-900 dark:text-slate-100">Data Protection</h4>
                <p>We implement appropriate security measures to protect your personal information. Your data is stored securely and is not shared with third parties without your consent, except as required by law.</p>

                <h4 class="font-medium text-slate-900 dark:text-slate-100">Your Rights</h4>
                <ul class="list-disc list-inside space-y-1 ml-4">
                    <li>Access and review your personal information</li>
                    <li>Request corrections to inaccurate data</li>
                    <li>Request deletion of your data (subject to legal requirements)</li>
                    <li>Opt-out of non-essential communications</li>
                </ul>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex justify-end">
            <button onclick="closePrivacyModal()" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                I Understand
            </button>
        </div>
    </div>
</div>

<script>
    function openTermsModal() {
        document.getElementById('terms-modal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeTermsModal() {
        document.getElementById('terms-modal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    function openPrivacyModal() {
        document.getElementById('privacy-modal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closePrivacyModal() {
        document.getElementById('privacy-modal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // Close modals when clicking outside
    document.getElementById('terms-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeTermsModal();
        }
    });

    document.getElementById('privacy-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            closePrivacyModal();
        }
    });

</script>
