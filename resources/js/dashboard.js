/**
 * Dashboard JavaScript functionality
 * Handles real-time updates and user interactions on the dashboard
 */

document.addEventListener('livewire:init', function () {
    // Update organization time every second
    const timeElement = document.getElementById('org-time');
    if (timeElement) {
        // Get timezone from the element's data or from a global variable
        const timezoneElement = timeElement.nextElementSibling;
        const timezone = timezoneElement ? timezoneElement.textContent.trim() : 'UTC';

        setInterval(function () {
            const now = new Date();

            try {
                const options = {
                    timeZone: timezone,
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                };

                const formatter = new Intl.DateTimeFormat('en-US', options);
                timeElement.textContent = formatter.format(now);
            } catch (e) {
                // Fallback if timezone is invalid
                timeElement.textContent = now.toLocaleTimeString('en-US', {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });
            }
        }, 1000);
    }

    // Listen for member check-in events to refresh dashboard data
    Livewire.on('member-checked-in', (data) => {
        // Optionally refresh dashboard components or show additional feedback
        console.log('Member checked in:', data);

        // You can add more functionality here like:
        // - Show toast notifications
        // - Update counters
        // - Refresh specific components
    });

    // Add any other dashboard-specific JavaScript functionality here
    console.log('Dashboard JavaScript initialized');
});
