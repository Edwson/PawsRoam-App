<?php
/**
 * English Common Translations for PawsRoam
 */

return [
    // General
    'app_name' => 'PawsRoam',
    'error_server_generic' => 'An unexpected server error occurred. Please try again later.',
    'error_method_not_allowed' => 'Method Not Allowed.',
    'error_csrf_token_invalid' => 'Invalid security token. Please refresh the page and try again.',
    'error_validation_failed' => 'Validation failed. Please check the form for errors.',
    'state_text_processing' => 'Processing...',
    'button_submit' => 'Submit',
    'button_save' => 'Save',
    'button_cancel' => 'Cancel',
    'button_close' => 'Close',
    'yes' => 'Yes',
    'no' => 'No',
    'search_placeholder' => 'Search...',

    // Page Titles (examples, specific pages might override)
    'page_title_home' => 'Welcome to PawsRoam',
    'page_title_login' => 'Login - PawsRoam',
    'page_title_register' => 'Register - PawsRoam',
    'page_title_profile' => 'My Profile - PawsRoam',
    'page_title_admin_dashboard' => 'Admin Dashboard - PawsRoam',
    'page_title_404' => 'Page Not Found - PawsRoam',


    // Registration Page (pawsroam-webapp/pages/auth/register.php)
    'register_join_community_text' => 'Create your account to join our amazing pet-loving community!',
    'placeholder_username' => 'Enter your username',
    'label_username' => 'Username',
    'help_text_username' => '3-25 characters, letters, numbers, and underscores only.',
    'placeholder_email' => 'Enter your email address',
    'label_email' => 'Email Address',
    'placeholder_password' => 'Create a password',
    'label_password' => 'Password',
    'help_text_password' => 'Min. 8 characters. Include uppercase, lowercase, number, and symbol for strength.',
    'placeholder_confirm_password' => 'Confirm your password',
    'label_confirm_password' => 'Confirm Password',
    'link_text_terms' => 'Terms of Service',
    'link_text_privacy' => 'Privacy Policy',
    'label_agree_terms_with_links %s %s' => 'I agree to the %s and the %s.', // %s will be replaced by links
    'button_create_account' => 'Create Account',
    'text_already_have_account' => 'Already have an account?',
    'link_text_login_now' => 'Log In Now',
    'alert_registration_success' => 'Registration successful! You can now log in.', // Or "Please check your email to verify your account."
    'alert_registration_failed_unknown' => 'Registration failed. Please check the form and try again.',
    'alert_registration_failed_network' => 'A network error occurred. Please check your connection and try again.',

    // Registration API (pawsroam-webapp/api/v1/auth/register.php)
    'error_username_required' => 'Username is required.',
    'error_username_length' => 'Username must be between 3 and 25 characters.',
    'error_username_format' => 'Username can only contain letters, numbers, and underscores.',
    'error_email_required' => 'Email address is required.',
    'error_email_invalid' => 'Please enter a valid email address.',
    'error_email_taken' => 'This email address is already registered. Please try logging in.',
    'error_password_required' => 'Password is required.',
    'error_password_min_length' => 'Password must be at least 8 characters long.',
    'error_password_complexity' => 'Password must include an uppercase letter, a lowercase letter, a number, and a special character.', // Example if complexity rules are enforced
    'error_confirm_password_required' => 'Please confirm your password.',
    'error_passwords_do_not_match' => 'Passwords do not match.',
    'error_agree_terms_required' => 'You must agree to the Terms of Service and Privacy Policy.',
    'success_registration' => 'Registration successful! You can now log in.', // API success message
    'error_registration_failed_db' => 'Registration could not be completed due to a server issue. Please try again later.',

    // Login Page (to be created)
    'page_title_login' => 'Log In to PawsRoam',
    'login_welcome_text' => 'Welcome back! Log in to continue your PawsRoam adventure.',
    'label_remember_me' => 'Remember Me',
    'button_login' => 'Log In',
    'link_text_forgot_password' => 'Forgot Password?',
    'text_dont_have_account' => "Don't have an account?",
    'link_text_register_now' => 'Register Now',
    'alert_login_failed_credentials' => 'Invalid email or password. Please try again.',
    'alert_login_failed_unknown' => 'Login failed due to an unexpected error. Please try again.',
    'alert_login_failed_network' => 'A network error occurred during login. Please check your connection.',
    'alert_logout_success' => 'You have been successfully logged out.',
    'success_login_redirecting' => 'Login successful! Redirecting...',
    'error_account_pending_verification' => 'Your account is pending verification. Please check your email.',
    'error_account_suspended' => 'Your account has been suspended. Please contact support.',
    'error_account_inactive_contact_support' => 'Your account is not active. Please contact support.',
    'error_login_session_failed' => 'Could not start a user session. Please try again.',

    // Header/Navigation (to be created/updated)
    'nav_home' => 'Home',
    'nav_search' => 'Search Places',
    'nav_community' => 'PawsConnect',
    'nav_pawssafe' => 'PawsSafe',
    'nav_deals' => 'PawsCoupon', // Placeholder
    'nav_memorials' => 'PawsLove', // Placeholder
    'nav_login' => 'Log In',
    'nav_register' => 'Register',
    'nav_profile' => 'My Profile',
    'nav_my_pets' => 'My Pets',
    'nav_dashboard' => 'Dashboard', // Generic dashboard link
    'nav_admin' => 'Admin Panel',
    'nav_logout' => 'Log Out',
    'welcome_user' => 'Welcome, %s!', // %s for username
    'Toggle navigation' => 'Toggle navigation', // For navbar toggler aria-label
    'skip_to_main_content' => 'Skip to main content',

    // Meta descriptions and keywords
    'app_meta_description' => 'Discover amazing pet-friendly places, services, and connect with a global community of pet lovers with PawsRoam!',
    'app_meta_keywords' => 'pets, pet-friendly, travel, venues, map, community, dog, cat, animal care, pet services',

    // Homepage (pages/home.php)
    'Loading map...' => 'Loading map...',
    'Initializing interactive map...' => 'Initializing interactive map...',
    'Search pet-friendly places...' => 'Search pet-friendly places...', // For map search overlay (future)
    'Search this area' => 'Search this area',
    'welcome_to_pawsroam_title' => 'Welcome to PawsRoam!',
    'discover_connect_explore_text' => 'Your ultimate guide to discovering pet-friendly places, connecting with a vibrant community, and exploring the world with your furry companions.',
    'find_places_button' => 'Find Pet-Friendly Places',
    'join_community_button' => 'Join Our Community',
    'explore_community_button' => 'Explore Community',
    'how_pawsroam_works_title' => 'How PawsRoam Works',
    'feature_discover_title' => 'Discover',
    'feature_discover_text' => 'Easily find pet-friendly parks, cafes, hotels, and services near you or at your travel destination using our interactive map.',
    'feature_connect_title' => 'Connect',
    'feature_connect_text' => 'Join PawsConnect, our community forum. Share tips, arrange playdates, and connect with fellow pet lovers.',
    'feature_pawssafe_title' => 'PawsSafe',
    'feature_pawssafe_text' => 'Access our PawsSafe network for verified pet sitters and emergency care options when you need them most.',

    // Add more translations as features are developed...
];
