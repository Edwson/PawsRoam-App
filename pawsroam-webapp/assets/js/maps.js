// assets/js/maps.js
class PawsRoamMaps {
    constructor(mapElementId = 'map', defaultLat = 25.0330, defaultLng = 121.5654) {
        this.map = null;
        this.markers = []; // For business/venue markers
        this.infoWindows = [];
        this.userLocationMarker = null; // Separate marker for user's location
        this.userLocation = null;
        this.mapElementId = mapElementId;
        this.defaultLocation = { lat: defaultLat, lng: defaultLng }; // Taipei as default
        this.mapElement = document.getElementById(this.mapElementId);

        if (!this.mapElement) {
            console.error(`Map element with ID '${this.mapElementId}' not found in the DOM.`);
            return;
        }
        if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
            console.error('Google Maps API not loaded. Please ensure the API script is included correctly in your HTML before this script.');
            this.mapElement.innerHTML = '<p style="color:red; text-align:center; padding: 20px;">Google Maps API is not loaded. Map functionality is unavailable.</p>';
            return;
        }
    }

    async initMap() {
        if (!this.mapElement || typeof google === 'undefined' || typeof google.maps === 'undefined') {
            console.warn('Map initialization prerequisites not met (map element or Google API missing).');
            return;
        }

        this.map = new google.maps.Map(this.mapElement, {
            zoom: 13,
            center: this.defaultLocation,
            styles: this.getCustomMapStyles(),
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true,
            gestureHandling: 'cooperative'
        });

        try {
            await this.getCurrentLocation();
        } catch (error) {
            console.warn('Could not get current location, using default:', error.message);
            this.map.setCenter(this.defaultLocation);
        }

        // Initial load of businesses around the current map center (user location or default)
        const initialCenter = this.map.getCenter();
        if (initialCenter) {
            await this.loadNearbyBusinesses(initialCenter.lat(), initialCenter.lng());
        } else {
             await this.loadNearbyBusinesses(); // Fallback to default if center is somehow null
        }
        this.bindMapEvents();
    }

    async getCurrentLocation() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                return reject(new Error('Geolocation is not supported by this browser.'));
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    if (this.map) {
                        this.map.setCenter(this.userLocation);
                        this.map.setZoom(14);

                        if (this.userLocationMarker) {
                            this.userLocationMarker.setMap(null); // Remove old marker
                        }
                        this.userLocationMarker = new google.maps.Marker({
                            position: this.userLocation,
                            map: this.map,
                            title: 'Your Current Location',
                            icon: {
                                path: google.maps.SymbolPath.CIRCLE,
                                scale: 7,
                                fillColor: '#4285F4', // Google Blue
                                fillOpacity: 1,
                                strokeWeight: 2,
                                strokeColor: '#ffffff' // White border
                            },
                            zIndex: 1000 // Ensure it's above other markers if needed
                        });
                    }
                    resolve(this.userLocation);
                },
                (error) => {
                    let errorMessage = 'Geolocation permission denied or unavailable. ';
                    // Specific error messages can be helpful for debugging
                    switch(error.code) {
                        case error.PERMISSION_DENIED: errorMessage += "Please enable location services for PawsRoam in your browser settings."; break;
                        case error.POSITION_UNAVAILABLE: errorMessage += "Current location is temporarily unavailable."; break;
                        case error.TIMEOUT: errorMessage += "Request for location timed out."; break;
                        default: errorMessage += "An unknown error occurred while trying to get location."; break;
                    }
                    console.warn('Geolocation error:', error.message);
                    // Display this message to the user on the map or a notification area
                    // For example: if (this.mapElement) this.mapElement.insertAdjacentHTML('afterbegin', `<p class="map-error">${errorMessage}</p>`);
                    return reject(new Error(errorMessage)); // Reject with a user-friendly message
                },
                { enableHighAccuracy: true, timeout: 8000, maximumAge: 60000 }
            );
        });
    }

    async loadNearbyBusinesses(latitude, longitude, radiusKm = 5) {
        const lat = latitude || this.userLocation?.lat || this.defaultLocation.lat;
        const lng = longitude || this.userLocation?.lng || this.defaultLocation.lng;

        this.clearBusinessMarkers(); // Clear previous business markers

        try {
            const apiUrl = `/api/v1/business/search?lat=${lat}&lng=${lng}&radius=${radiusKm}`;
            const response = await fetch(apiUrl);

            if (!response.ok) {
                const errorText = await response.text();
                console.error(`API Error (${response.status}): ${errorText}`);
                throw new Error(`Failed to load businesses. Server responded with status ${response.status}.`);
            }

            const data = await response.json();

            if (data && data.businesses && Array.isArray(data.businesses)) {
                if (data.businesses.length === 0) {
                    console.info("No businesses found in the current map area.");
                    // Optionally display a message to the user on the map
                }
                this.addBusinessMarkers(data.businesses);
            } else {
                console.warn('No businesses array in API response or data is malformed:', data);
                throw new Error('Received invalid data from the business search API.');
            }

        } catch (error) {
            console.error('Error loading businesses:', error.message);
            // Display a user-friendly error on the map or a notification system
            // Example: this.showMapError("Could not load pet-friendly places. Please try again later.");
        }
    }

    addBusinessMarkers(businesses) {
        if (!this.map || !businesses) return;

        const bounds = new google.maps.LatLngBounds();
        if (this.userLocation) { // Ensure user location is part of bounds if available
            bounds.extend(this.userLocation);
        }


        businesses.forEach(business => {
            if (typeof business.latitude !== 'number' || typeof business.longitude !== 'number') {
                console.warn('Skipping business due to invalid/missing coordinates:', business.name || 'Unnamed');
                return;
            }
            const position = { lat: business.latitude, lng: business.longitude };
            const marker = new google.maps.Marker({
                position: position,
                map: this.map,
                title: business.name || 'Pet-Friendly Place',
                icon: this.getPawStarIcon(business.pawstar_rating, business.type || 'venue'),
                animation: google.maps.Animation.DROP
            });

            const infoWindow = this.createInfoWindow(business);
            marker.addListener('click', () => {
                this.closeAllInfoWindows();
                infoWindow.open({ anchor: marker, map: this.map });
                this.map.panTo(marker.getPosition());
                if (this.map.getZoom() < 15) this.map.setZoom(15); // Zoom in a bit on click if far out
            });

            this.markers.push(marker);
            this.infoWindows.push(infoWindow);
            bounds.extend(position);
        });

        if (this.markers.length > 0) {
            this.map.fitBounds(bounds);
            // Prevent over-zooming if bounds are very small (e.g. single point)
            if (this.map.getZoom() > 16 && this.markers.length > 1) {
                 this.map.setZoom(16);
            } else if (this.map.getZoom() > 15 && this.markers.length ===1) {
                 this.map.setZoom(15);
            }
        } else if (this.userLocation) {
            // If no businesses, but user location is known, ensure map is centered and reasonably zoomed there.
            this.map.setCenter(this.userLocation);
            this.map.setZoom(14);
        }
    }

    createInfoWindow(business) {
        const name = business.name ? this.escapeHTML(business.name) : 'Pet-Friendly Place';
        const rating = business.pawstar_rating ? `${'⭐'.repeat(parseInt(business.pawstar_rating, 10))}` : 'Pending review';
        const address = business.address ? this.escapeHTML(business.address) : 'Address not available';
        // Assuming slugs are implemented for user-friendly URLs, fallback to ID.
        const detailUrl = business.slug ? `/business/${this.escapeHTML(business.slug)}` : (business.id ? `/pages/business-detail.php?id=${business.id}` : '#');
        const detailLink = (business.slug || business.id) ? `<a href="${detailUrl}" target="_blank" title="View details for ${name}">More Info</a>` : '';

        const contentString = `
            <div class="pawsroam-infowindow" style="font-family: 'Inter', sans-serif; max-width: 280px; padding: 5px;">
                <h3 style="margin: 0 0 8px 0; font-size: 1.1em; color: var(--primary-orange, #FF6B35);">${name}</h3>
                <p style="margin: 0 0 5px 0; font-size: 0.9em;">Rating: ${rating}</p>
                <p style="margin: 0 0 8px 0; font-size: 0.85em; color: #555;">${address}</p>
                ${detailLink ? `<p style="margin: 5px 0 0 0; text-align: right;">${detailLink}</p>` : ''}
            </div>`;
        return new google.maps.InfoWindow({ content: contentString, maxWidth: 320 });
    }

    getPawStarIcon(ratingStr, type = 'venue') {
        const rating = parseInt(ratingStr, 10);
        let fillColor = '#3498DB'; // Default Blue (Nominee/Pending)
        let pawPath = google.maps.SymbolPath.PAW; // Default to PAW symbol

        if (rating >= 3) fillColor = '#F1C40F'; // Gold for 3 stars
        else if (rating === 2) fillColor = '#95A5A6'; // Silver for 2 stars
        else if (rating === 1) fillColor = '#E67E22'; // Bronze for 1 star

        // Future: Could use different paths for different venue types
        // if (type === 'park') pawPath = google.maps.SymbolPath.PARK_BENCH; // Example

        return {
            path: pawPath,
            fillColor: fillColor,
            fillOpacity: 1,
            strokeWeight: 0.8,
            strokeColor: '#2C3E50', // Dark outline
            scale: 1.6,
            anchor: new google.maps.Point(0, 2.5), // Adjust anchor if needed
        };
    }

    getCustomMapStyles() {
        return [ /* Using default Google Maps style for now, can add custom styles later */ ];
        // Example from before (can be uncommented and customized)
        /*
        return [
            { "featureType": "poi.business", "stylers": [ { "visibility": "off" } ] }, // Hide default business POIs
            { "featureType": "road", "elementType": "labels.icon", "stylers": [ { "visibility": "off" } ] },
            { "featureType": "transit", "stylers": [ { "visibility": "off" } ] },
            { "featureType": "water", "elementType": "geometry.fill", "stylers": [ { "color": "#B2EBF2" } ] }, // Light Cyan
            { "featureType": "landscape.natural", "elementType": "geometry.fill", "stylers": [ { "color": "#E6EE9C" } ] } // Light Lime
        ];
        */
    }

    bindMapEvents() {
        if (!this.map) return;

        // "Search this area" button functionality (button needs to exist in HTML)
        const searchAreaButton = document.getElementById('search-map-area-button');
        if (searchAreaButton) {
            searchAreaButton.addEventListener('click', () => {
                const center = this.map.getCenter();
                if (center) {
                    this.loadNearbyBusinesses(center.lat(), center.lng());
                }
            });
        } else {
            // If no button, consider a subtle dragend listener with debounce
            let dragEndTimeout;
            this.map.addListener('dragend', () => {
                clearTimeout(dragEndTimeout);
                dragEndTimeout = setTimeout(() => {
                    const center = this.map.getCenter();
                    if (center) {
                         console.log('Map idle. New center for potential search:', center.lat(), center.lng());
                         // this.loadNearbyBusinesses(center.lat(), center.lng()); // Auto-search on drag
                    }
                }, 1000); // 1 second debounce
            });
        }

        this.map.addListener('zoom_changed', () => {
            // console.log('Zoom level:', this.map.getZoom());
        });
    }

    closeAllInfoWindows() {
        this.infoWindows.forEach(iw => iw.close());
    }

    clearBusinessMarkers() {
        this.markers.forEach(marker => marker.setMap(null));
        this.markers = [];
        this.closeAllInfoWindows(); // Also close any open info windows
        this.infoWindows = [];
    }

    escapeHTML(str) {
        if (str === null || typeof str === 'undefined') return '';
        const p = document.createElement('p');
        p.textContent = str;
        return p.innerHTML;
    }

    // Public method to allow external triggers for searching
    publicSearchArea(latitude, longitude, radiusKm = 5) {
        if (!this.map) {
            console.warn('Map not initialized. Cannot execute publicSearchArea.');
            return;
        }
        const newCenter = new google.maps.LatLng(latitude, longitude);
        this.map.panTo(newCenter); // Smoothly pan
        this.map.setZoom(14); // Reasonable zoom for a new search area
        this.loadNearbyBusinesses(latitude, longitude, radiusKm);
    }

    // Helper to show errors on the map (basic example)
    showMapError(message) {
        if(this.mapElement) {
            let errorDiv = this.mapElement.querySelector('.pawsroam-map-error-overlay');
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.className = 'pawsroam-map-error-overlay';
                errorDiv.style.cssText = 'position:absolute; top:10px; left:50%; transform:translateX(-50%); background:rgba(255,0,0,0.8); color:white; padding:10px 20px; border-radius:5px; z-index:1000;';
                this.mapElement.style.position = 'relative'; // Ensure parent is positioned
                this.mapElement.appendChild(errorDiv);
            }
            errorDiv.textContent = message;
            setTimeout(() => { // Auto-hide after some time
                if (errorDiv) errorDiv.remove();
            }, 5000);
        }
    }
}

// --- Standard Initialization ---
// This should be called when the DOM is ready AND Google Maps API script has finished loading.
// A common way is to use the `callback` parameter in the Google Maps API script URL.
// e.g., <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initPawsRoamAppMap"></script>
//
// function initPawsRoamAppMap() {
//     if (document.getElementById('map')) {
//         const pawsRoamMapInstance = new PawsRoamMaps('map');
//         pawsRoamMapInstance.initMap().catch(error => {
//             console.error("Fatal error initializing PawsRoam map:", error);
//             const mapElement = document.getElementById('map');
//             if (mapElement) {
//                 mapElement.innerHTML = `<p style="color: red; text-align: center; padding: 20px;">Could not initialize map: ${error.message}. Please try refreshing.</p>`;
//             }
//         });
//         // Make instance globally available if needed for other scripts, or manage through modules
//         // window.pawsRoamMap = pawsRoamMapInstance;
//     }
// }
//
// If not using callback, then:
// document.addEventListener('DOMContentLoaded', () => {
//    // Check if google.maps is available, if not, wait or handle
//    if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
//        initPawsRoamAppMap();
//    } else {
//        console.warn('Google Maps API not ready at DOMContentLoaded. Map initialization might be delayed or fail.');
//        // You might need a more robust way to wait for the API if it loads asynchronously
//        // without a callback, e.g., a polling mechanism or a custom event.
//    }
// });
