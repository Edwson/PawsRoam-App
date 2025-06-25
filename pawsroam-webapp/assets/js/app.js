/**
 * PawsRoam Main Application JavaScript
 * Version: 1.0.1
 */

// --- Google Maps API Global Callback ---
/**
 * Global callback function invoked when Google Maps API script is loaded.
 * This function is specified in the `callback` parameter of the Maps API script URL in header.php.
 * It checks for the presence of a map container on the page and initializes PawsRoamMaps if found.
 */
function initPawsRoamAppMapGlobal() {
    console.log("PawsRoam: Google Maps API script has loaded (callback: initPawsRoamAppMapGlobal).");

    const mapElement = document.getElementById('map');

    if (mapElement) {
        console.log("PawsRoam: Map element found on page. Attempting to initialize PawsRoamMaps...");
        if (typeof PawsRoamMaps === 'function') {
            const pawsRoamMapInstance = new PawsRoamMaps('map');

            pawsRoamMapInstance.initMap()
                .then(() => {
                    console.log("PawsRoam: PawsRoamMaps initialized successfully.");
                    const placeholder = mapElement.querySelector('.map-loading-placeholder');
                    if (placeholder) {
                        placeholder.style.display = 'none'; // Hide loading placeholder
                    }
                    // The PawsRoamMaps class itself should manage the 'Search this area' button visibility
                    // based on map events like 'idle' or 'dragend'.
                })
                .catch(error => {
                    console.error("PawsRoam: Error initializing PawsRoamMaps:", error);
                    mapElement.innerHTML = `<div class="alert alert-danger m-3" role="alert">Could not initialize map: ${escapeHtml(error.message)}</div>`;
                });
        } else {
            console.error("PawsRoam: PawsRoamMaps class is not defined. Ensure maps.js is loaded before this callback is triggered or maps.js is included before app.js if not using async/defer properly.");
            mapElement.innerHTML = '<div class="alert alert-warning m-3" role="alert">Map library (PawsRoamMaps) not found.</div>';
        }
    } else {
        console.log("PawsRoam: No map element (id='map') found on this page. Skipping map initialization.");
    }

    // Dispatch a custom event that other parts of the app can listen to
    document.dispatchEvent(new CustomEvent('pawsRoamGoogleMapsApiReady', { detail: { status: mapElement ? 'attempted' : 'skipped' } }));
}


// --- DOMContentLoaded Event Listener ---
document.addEventListener('DOMContentLoaded', function () {
    console.log("PawsRoam App JS: DOMContentLoaded event fired.");

    // Initialize Bootstrap components that require JS activation
    initializeBootstrapComponents();

    // Service Worker Registration
    registerServiceWorker();

    // Example: Add event listener for the logout form in navigation if it exists
    // This is an alternative to inline onclick, makes JS cleaner.
    const logoutFormNav = document.getElementById('logoutFormNav');
    if (logoutFormNav) {
        const logoutLink = document.querySelector('a[onclick*="logoutFormNav.submit()"]');
        if (logoutLink) {
            logoutLink.addEventListener('click', function(event) {
                event.preventDefault();
                // Optionally, add a confirmation dialog here
                // if (confirm("Are you sure you want to log out?")) {
                //     logoutFormNav.submit();
                // }
                logoutFormNav.submit();
            });
        }
    }


    // --- Any other global initializations ---
    console.log("PawsRoam: Global initializations on DOMContentLoaded complete.");

}); // End DOMContentLoaded


/**
 * Initializes Bootstrap components that require JavaScript activation.
 */
function initializeBootstrapComponents() {
    if (typeof bootstrap !== 'undefined') {
        // Initialize all tooltips
        try {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        } catch (e) { console.warn("Bootstrap Tooltip initialization failed:", e); }

        // Initialize all popovers
        try {
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
        } catch (e) { console.warn("Bootstrap Popover initialization failed:", e); }

        // Initialize all dropdowns (often work with data attributes but explicit init is robust)
        try {
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            dropdownElementList.map(function (dropdownToggleEl) {
              return new bootstrap.Dropdown(dropdownToggleEl);
            });
        } catch (e) { console.warn("Bootstrap Dropdown initialization failed:", e); }

        // Initialize Toasts if any are present and need JS init
        try {
            var toastElList = [].slice.call(document.querySelectorAll('.toast'));
            toastElList.map(function (toastEl) {
              return new bootstrap.Toast(toastEl/*, options */);
            });
        } catch (e) { console.warn("Bootstrap Toast initialization failed:", e); }

        console.log("PawsRoam: Bootstrap components initialized.");
    } else {
        console.warn("PawsRoam: Bootstrap JS not detected. Some UI components may not function as expected.");
    }
}

/**
 * Registers the PawsRoam service worker.
 */
function registerServiceWorker() {
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => { // Register SW after page is fully loaded for performance
            navigator.serviceWorker.register('/service-worker.js', { scope: '/' }) // Explicit scope
                .then(registration => {
                    console.log('PawsRoam: ServiceWorker registered successfully with scope:', registration.scope);

                    registration.onupdatefound = () => {
                        const installingWorker = registration.installing;
                        if (installingWorker) {
                            installingWorker.onstatechange = () => {
                                if (installingWorker.state === 'installed') {
                                    if (navigator.serviceWorker.controller) {
                                        console.log('PawsRoam: New ServiceWorker content is available and will be used when all tabs for this scope are closed.');
                                        // TODO: Implement a user notification (e.g., a toast) to inform about the update
                                        // and potentially offer a button to activate the new SW immediately.
                                        // Example: showUpdateAvailableNotification(registration);
                                    } else {
                                        console.log('PawsRoam: ServiceWorker content is cached for offline use.');
                                    }
                                }
                            };
                        }
                    };
                })
                .catch(error => {
                    console.error('PawsRoam: ServiceWorker registration failed:', error);
                });
        });
    } else {
        console.log('PawsRoam: ServiceWorker not supported in this browser.');
    }
}

/**
 * Simple HTML escaping function.
 * @param {string} str String to escape.
 * @returns {string} Escaped string.
 */
function escapeHtml(str) {
    if (typeof str !== 'string') return '';
    return str
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

console.log("PawsRoam: app.js script loaded and parsed.");
