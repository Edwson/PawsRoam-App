# PawsRoam Web Application

Welcome to PawsRoam, the ultimate pet-friendly discovery platform! This application is designed to help pet owners find venues, services, and connect with a global community of fellow pet lovers.

## Project Overview

PawsRoam aims to be a comprehensive, multi-language platform featuring:
-   Hyper-Intelligent Map Discovery for pet-friendly locations.
-   A credible PawStar rating system for venues.
-   The PawsSafe Emergency Network for pet care.
-   A PawsAI Assistant powered by Google Gemini for pet advice.
-   PawsConnect Social Network for community engagement.
-   PawsCoupon Intelligence for deals and offers.
-   PawsLove Memorial Platform to remember beloved pets.

## Technology Stack

-   **Backend**: PHP 8.1+
-   **Database**: MySQL 8.0+ (Managed via PHPMyAdmin)
-   **Frontend**: HTML5, CSS3, Vanilla JavaScript (ES6+)
-   **APIs**: Google Maps API, Google Gemini AI API
-   **PWA**: Service Worker + Web App Manifest

## Current Status

This `README.md` is a placeholder. The application is currently in the initial setup phase. Core functionalities and features are under development.

## File Structure

The project follows a specific file structure outlined in the main project brief. Key directories include:
-   `api/`: REST API endpoints.
-   `assets/`: CSS, JavaScript, images, and fonts.
-   `config/`: Configuration files (database, API keys, constants).
-   `database/`: SQL schema, migrations, and seeds.
-   `includes/`: Shared PHP components (functions, auth, translation).
-   `lang/`: Server-side translation files.
-   `pages/`: Main application page scripts.
-   `plugins/`: Extensible plugin system.
-   `tests/`: Testing suite.
-   `themes/`: Theme system.
-   `uploads/`: User-uploaded files.

## Getting Started (Placeholder)

Detailed setup instructions will be provided as development progresses. For now, ensure you have a compatible PHP and MySQL environment.

1.  **Clone the repository (if applicable).**
2.  **Set up your web server** (e.g., Apache, Nginx) to point to the `pawsroam-webapp` directory as the web root.
3.  **Create the database**:
    -   Import the schema from `database/pawsroam_db.sql` into your MySQL server.
    -   Ensure the database name is `pawsroam_db` or update `config/database.php` and your `.env` file.
4.  **Configure environment variables**:
    -   Copy `config/.env.example` to `config/.env`.
    -   Update `.env` with your database credentials (`DB_HOST`, `DB_NAME`, `DB_USERNAME`, `DB_PASSWORD`).
    -   Add your `GEMINI_AI_API_KEY` and Google Maps API Key to `config/api_keys.php` (or manage them via `.env` and ensure `api_keys.php` loads them).
5.  **Install PHP dependencies (if any become necessary)**:
    ```bash
    composer install
    ```
6.  **Install frontend dependencies (if any build tools are used)**:
    ```bash
    npm install
    ```
7.  **Access the application** through your web browser.

## Key Implementation Details

-   **Multi-language Support**: All user-facing text is managed via a translation system (`includes/translation.php` and `lang/` directory).
-   **Database Interaction**: Uses a custom `Database` class (`config/database.php`) with PDO for MySQL connections.
-   **API Integrations**:
    -   Google Gemini AI: `api/v1/ai/gemini.php`
    -   Google Maps: `assets/js/maps.js`
-   **PWA**: `service-worker.js` and `manifest.json` provide Progressive Web App capabilities.
-   **.htaccess**: Configures URL rewriting, security headers, caching, and compression for Apache servers.

## Development Guidelines (Summary)

-   **Mobile-First Responsive Design**
-   **Accessibility (WCAG 2.1 AA)**
-   **Security-First**: Validate inputs, use prepared statements, protect against common vulnerabilities.
-   **Performance-Conscious**: Optimize assets, implement caching.
-   **Modular Architecture**: Design for future scalability with plugins and themes.

## Contributing (Placeholder)

Contribution guidelines will be added later.

## License (Placeholder)

The licensing terms for this project will be defined later. Assume it is proprietary unless otherwise stated.

---

🐾 *PawsRoam - Connecting Pets, People, and Places.* 🐾
