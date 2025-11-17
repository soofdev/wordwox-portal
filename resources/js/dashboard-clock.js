/**
 * Dashboard Organization Clock
 * Updates the organization time display every second
 */

document.addEventListener('livewire:init', function () {
    // Get timezone from the data attribute on the time element
    const timeElement = document.getElementById('org-time');

    if (!timeElement) {
        return; // No time element found, exit early
    }

    const timezone = timeElement.dataset.timezone;

    if (!timezone) {
        return; // No timezone data, exit early
    }

    // Update time immediately
    updateTime();

    // Update time every second
    setInterval(updateTime, 1000);

    function updateTime() {
        try {
            const now = new Date();
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
            const now = new Date();
            timeElement.textContent = now.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        }
    }
});
