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

    // Business Search API (api/v1/business/search.php)
    'error_invalid_latitude' => 'Invalid latitude provided. Must be between -90 and 90.',
    'error_invalid_longitude' => 'Invalid longitude provided. Must be between -180 and 180.',
    'error_invalid_radius' => 'Invalid search radius provided. Must be a positive number within allowed limits.',
    'error_invalid_limit' => 'Invalid result limit provided. Must be a positive number within allowed limits.',
    'error_validation_failed_search_params' => 'Invalid search parameters provided. Please check your input.',
    'info_no_businesses_found_nearby' => 'No pet-friendly places were found matching your criteria in this area. Try expanding your search!',
    'error_server_generic_search_failed' => 'Could not perform the search due to a server error. Please try again in a few moments.',

    // Business Detail Page (pages/business-detail.php)
    'page_title_business_detail_default' => 'Business Details',
    'error_business_not_found' => 'The requested business could not be found or is not currently active.',
    'error_server_generic_page_load' => 'Could not load page details due to a server error.',
    'error_no_business_specified' => 'No business was specified to display.',
    'page_title_error' => 'Error',
    'error_oops_title' => 'Oops! Something went wrong.',
    'try_again_later_or_contact_support_text' => 'Please try again later, or if the problem persists, contact support.',
    'go_to_homepage_link_text' => 'Go to Homepage',
    'recognitions_text' => 'recognitions',
    'address_placeholder_short' => 'City, Region (e.g., Pawsville, Petland)',
    'about_this_place_title' => 'About This Place',
    'pet_policies_title' => 'Pet Policies',
    'allows_off_leash_label' => 'Off-leash Allowed:',
    'has_water_bowls_label' => 'Water Bowls Provided:',
    'has_pet_menu_label' => 'Pet Menu Available:',
    'pet_size_limit_label' => 'Pet Size Limit:',
    'weight_limit_kg_label' => 'Weight Limit (kg):',
    'policy_note_contact_business' => 'Note: Policies can change. Please contact the business directly for the most up-to-date information.',
    'amenities_title' => 'Amenities',
    'amenities_placeholder_text' => '(List of amenities like outdoor seating, waste bags, etc., will appear here)',
    'photos_title' => 'Photos',
    'photos_placeholder_text' => '(A photo gallery of this place will appear here)',
    'user_reviews_title' => 'User Reviews',
    'reviews_placeholder_text' => '(User reviews and a form to submit your own review will appear here)',
    'map_placeholder_text' => '(Map showing business location)',
    'location_and_contact_title' => 'Location & Contact',
    'address_label' => 'Address:',
    'address_placeholder_text' => '(Full address will appear here)',
    'phone_label' => 'Phone:',
    'phone_placeholder_text' => '(Phone number will appear here)',
    'website_label' => 'Website:',
    'website_placeholder_text' => '(Website link will appear here)',
    'get_directions_button' => 'Get Directions',
    'actions_title' => 'Actions',
    'add_to_favorites_button' => 'Add to Favorites',
    'share_this_place_button' => 'Share This Place',
    'report_issue_button' => 'Report an Issue',
    'edit_business_button_admin' => 'Edit Business (Admin)',
    'error_map_load_failed' => 'Map could not be loaded.',
    'error_map_api_key_missing' => 'Map functionality is limited due to a configuration issue.',

    // User Profile Page (pages/profile.php)
    'error_profile_load_failed_db' => 'Could not load your profile due to a database error. Please try refreshing the page.',
    'error_profile_load_failed_server' => 'Could not load your profile due to a server error. Please try again later.',
    'error_profile_not_logged_in' => 'You must be logged in to view your profile. Please log in first.',
    'page_title_user_profile' => 'My Profile',
    'profile_last_login_placeholder' => 'Last login: (Placeholder)',
    'profile_section_account_details' => 'Account Details',
    'profile_subsection_basic_info' => 'Basic Information',
    'profile_email_change_note' => 'Changing your email address will require re-verification. (This feature is not yet active).',
    'profile_member_since' => 'Member Since',
    'profile_account_status' => 'Account Status',
    'profile_subsection_preferences' => 'Preferences',
    'profile_language_label' => 'Preferred Language',
    'profile_timezone_label' => 'Timezone',
    'profile_select_timezone_option' => '-- Select Your Timezone --',
    'profile_subsection_change_password' => 'Change Password',
    'profile_current_password_label' => 'Current Password',
    'profile_password_leave_blank_note' => 'Required only if changing password.',
    'help_text_password_profile' => 'Minimum 8 characters. Leave blank if not changing.',
    'profile_new_password_label' => 'New Password',
    'profile_confirm_new_password_label' => 'Confirm New Password',
    'profile_button_update' => 'Update Profile',
    'profile_update_api_note' => 'Profile updates will be handled by an API endpoint (not yet implemented).',
    'profile_sidebar_avatar_title' => 'Profile Picture',
    'profile_avatar_alt_text_user %s' => '%s\'s profile picture', // %s for username
    'profile_button_change_avatar' => 'Change Avatar',
    'profile_sidebar_quick_links_title' => 'Quick Links',
    'profile_link_my_bookings' => 'My PawsSafe Bookings',
    'profile_link_my_reviews' => 'My Reviews',
    'profile_link_delete_account' => 'Delete Account',
    'profile_alert_update_success_stub' => 'Profile updated successfully! (This is a stub message)',
    'error_username_taken_stub' => 'This username is already taken. (Stub error)',
    'error_password_min_length_stub' => 'New password is too short. (Stub error)',
    'profile_alert_update_failed_stub' => 'Profile update failed. Please check errors. (This is a stub message)',

    // Pet Profile Management Page (pages/pet-profile.php)
    'page_title_pet_profiles' => 'My Pet Profiles',
    'tooltip_add_new_pet' => 'Add a new pet to your profile (Feature coming soon)',
    'button_add_new_pet' => 'Add New Pet',
    'error_pet_profiles_load_failed_db' => 'Could not load your pet profiles due to a database error. Please try again.',
    'pet_profiles_list_title' => 'Your Registered Pets',
    'alt_text_no_pets_illustration' => 'Illustration of an empty pet carrier or a pet looking sad',
    'pet_profiles_none_found_message' => "You haven't added any pet profiles yet.",
    'button_add_your_first_pet_link_text' => 'add your first pet profile',
    'pet_profiles_add_one_prompt_html %s' => 'It looks a bit empty here! Why not %s and share details about your furry, feathery, or scaly friend?', // %s is for the link
    'tooltip_add_first_pet' => 'Add your first pet (Feature coming soon)',
    'button_add_your_first_pet' => 'Add Your First Pet', // Button text if not using the link version
    'pet_profiles_stub_note' => '(Pet listing and management functionality is currently a stub and will be implemented soon!)',
    'pet_avatar_alt %s' => '%s\'s avatar', // %s for pet name
    'tooltip_view_pet %s' => 'View %s\'s full profile (Coming soon)',
    'button_view' => 'View',
    'tooltip_edit_pet %s' => 'Edit %s\'s profile (Coming soon)',
    // 'button_edit' => 'Edit', // Already have a general 'button_edit'
    'tooltip_delete_pet %s' => 'Delete %s\'s profile (Coming soon)',
    // 'button_delete' => 'Delete', // Already have a general 'button_delete'
    'modal_title_delete_pet_confirm %s' => 'Confirm Deletion of %s', // %s for pet name
    'modal_title_delete_pet_confirm_generic' => 'Confirm Pet Deletion',
    'modal_body_delete_pet_warning %s' => 'Are you sure you want to delete the profile for %s? This action cannot be undone.', // %s for pet name
    'modal_body_delete_pet_warning_generic' => 'Are you sure you want to delete this pet profile? This action cannot be undone.',
    'button_delete_confirm' => 'Yes, Delete Pet',

    // Admin Dashboard Page (pages/admin/dashboard.php)
    // 'page_title_admin_dashboard' is already defined
    'role_display_text %s' => 'Role: %s', // %s for role name
    'admin_dashboard_welcome_user %s' => 'Welcome, %s! This is your PawsRoam administration hub.', // %s for username
    'admin_dashboard_quick_stats_title' => 'Platform At a Glance',
    'admin_stat_total_users' => 'Total Users',
    'admin_stat_pending_businesses' => 'Pending Businesses',
    'admin_stat_pending_businesses_link_aria' => 'View pending businesses for approval',
    'admin_stat_active_pawssafe' => 'Active PawsSafe Providers',
    'admin_stat_active_pawssafe_link_aria' => 'View active PawsSafe providers',
    'admin_stat_total_active_venues' => 'Total Active Venues',
    'admin_stat_total_venues_link_aria' => 'View all active venues',
    'admin_dashboard_stats_stub_note' => '(Note: Statistics shown are placeholders and will be dynamic in future updates.)',
    'admin_dashboard_management_sections_title' => 'Management Tools',
    'admin_link_manage_users' => 'User Management',
    'admin_link_manage_users_desc' => 'View, edit, suspend, or delete user accounts.',
    'admin_link_manage_businesses' => 'Business Management',
    'admin_link_manage_businesses_desc' => 'Approve new business listings, edit existing ones, or manage flags.',
    'admin_link_manage_pawssafe' => 'PawsSafe Provider Management',
    'admin_link_manage_pawssafe_desc' => 'Verify, approve, and manage PawsSafe emergency care providers.',
    'admin_link_manage_translations' => 'Content Translations',
    'admin_link_manage_translations_desc' => 'Oversee and edit translations for various site content.',
    'admin_link_view_analytics' => 'Platform Analytics',
    'admin_link_view_analytics_desc' => 'Review key platform metrics, user engagement, and growth reports.',
    'admin_link_system_settings' => 'System Settings',
    'admin_link_system_settings_desc' => 'Configure global application settings, API keys, and maintenance modes.',
    'admin_link_disabled_tooltip' => 'This feature is under development and will be available soon!',
    'admin_dashboard_links_stub_note' => '(Note: Management links are placeholders for upcoming features.)',

    // Add Pet Page (pages/pets/add-pet.php)
    'page_title_add_pet' => 'Add New Pet Profile',
    'button_back_to_my_pets' => 'Back to My Pets',
    'add_pet_form_title' => 'Tell Us About Your Pet',
    'add_pet_section_basic_info' => 'Basic Information',
    'placeholder_pet_name' => "Enter your pet's name",
    'label_pet_name' => "Pet's Name",
    'select_option_placeholder' => '-- Select ', // Used as prefix, e.g., "-- Select Species --"
    'label_pet_species' => 'Species',
    'pet_species_dog' => 'Dog',
    'pet_species_cat' => 'Cat',
    'pet_species_bird' => 'Bird',
    'pet_species_rabbit' => 'Rabbit',
    'pet_species_other' => 'Other',
    'placeholder_pet_breed' => 'e.g., Golden Retriever, Siamese (Optional)',
    'label_pet_breed' => 'Breed (Optional)',
    'label_pet_birthdate' => 'Birthdate (Optional)',
    'add_pet_section_physical_details' => 'Physical Details',
    'label_pet_size' => 'Size (Optional)',
    'pet_size_label' => 'Size', // For the "-- Select Size --" placeholder part
    'pet_size_small' => 'Small (e.g., Chihuahua, Domestic Shorthair)',
    'pet_size_medium' => 'Medium (e.g., Beagle, Cocker Spaniel)',
    'pet_size_large' => 'Large (e.g., Labrador, German Shepherd)',
    'pet_size_extra_large' => 'Extra Large (e.g., Great Dane, Maine Coon)',
    'label_pet_weight_kg' => 'Weight (kg, Optional)',
    'add_pet_section_characteristics' => 'Characteristics & Care',
    'label_pet_personality' => 'Personality Traits (Optional)',
    'placeholder_pet_personality' => 'e.g., friendly, playful, shy, loves cuddles, very vocal',
    'help_text_pet_personality' => 'Enter comma-separated traits or a short description.',
    'label_pet_medical' => 'Medical Conditions (Optional)',
    'placeholder_pet_medical' => 'e.g., allergies (pollen, chicken), past surgeries, daily medication for arthritis',
    'label_pet_dietary' => 'Dietary Restrictions (Optional)',
    'placeholder_pet_dietary' => 'e.g., grain-free, no chicken, specific brand of food, sensitive stomach',
    'add_pet_section_avatar' => 'Profile Picture',
    'label_pet_avatar' => 'Upload Avatar (Optional)',
    'help_text_pet_avatar' => 'Max 2MB. Recommended: square image. JPG, PNG, GIF allowed.',
    'alt_pet_avatar_preview' => "Pet's avatar preview",
    'button_add_pet_submit' => 'Add This Pet',
    'add_pet_alert_success' => 'Pet profile added successfully!',
    'add_pet_alert_failed_unknown' => 'Failed to add pet. Please check the form for errors and try again.',
    'add_pet_alert_failed_network' => 'A network error occurred while adding your pet. Please check your connection and try again.',

    // Add Pet API (api/v1/pets/create.php) - specific API messages
    'error_pet_name_required' => "Pet's name is required.",
    'error_pet_name_too_long' => "Pet's name cannot exceed 100 characters.",
    'error_pet_species_required' => 'Please select a species for your pet.',
    'error_pet_species_invalid' => 'The selected species is not valid.',
    'error_pet_breed_too_long' => 'Breed information cannot exceed 100 characters.',
    'error_pet_size_invalid' => 'The selected size is not valid.',
    'error_pet_weight_invalid' => 'Please enter a valid weight for your pet (e.g., 5.5). It must be a positive number.',
    'error_pet_birthdate_invalid_format' => 'Invalid birthdate format. Please use YYYY-MM-DD.',
    'error_pet_birthdate_future' => "Pet's birthdate cannot be in the future.",
    'error_pet_avatar_upload_failed_code_1' => 'The uploaded avatar exceeds the maximum allowed file size (server limit).',
    'error_pet_avatar_upload_failed_code_2' => 'The uploaded avatar exceeds the maximum allowed file size (form limit).',
    'error_pet_avatar_upload_failed_code_3' => 'The avatar was only partially uploaded. Please try again.',
    'error_pet_avatar_upload_failed_code_4' => 'No avatar file was selected for upload.', // More accurate for UPLOAD_ERR_NO_FILE
    'error_pet_avatar_upload_failed_code_6' => 'Server error: Missing a temporary folder for avatar upload.',
    'error_pet_avatar_upload_failed_code_7' => 'Server error: Failed to write avatar file to disk.',
    'error_pet_avatar_upload_failed_generic' => 'An error occurred during avatar upload. Please try again.',
    'add_pet_api_success' => 'New pet profile created successfully!',
    'add_pet_api_failed_db' => 'Failed to create pet profile due to a database error. Please try again later.',

    // Profile Update API (api/v1/user/profile-update.php)
    'error_profile_update_mismatch' => 'Profile update authorization failed. Please ensure you are logged in correctly.',
    // 'error_username_taken' is reused
    'error_language_preference_required' => 'Language preference is required.',
    'error_language_preference_invalid' => 'The selected language is not supported.',
    'error_timezone_required' => 'Timezone selection is required.',
    'error_timezone_invalid' => 'The selected timezone is not valid.',
    'error_current_password_required_for_change' => 'Your current password is required to set a new one.',
    'error_current_password_incorrect' => 'The current password you entered is incorrect.',
    'profile_update_no_changes' => 'No changes were detected in your profile information.',
    'profile_update_success' => 'Your profile has been updated successfully!',
    'profile_update_failed_db' => 'Failed to update your profile due to a database error. Please try again.',
    'profile_update_language_changed_refresh_note' => 'Language preference changed. The page will reload in 3 seconds to apply changes.',
    'profile_update_failed_generic_error' => 'Profile update failed. Please review the errors below or try again.',
    'profile_update_failed_network' => 'A network error occurred while updating your profile. Please check your connection and try again.',

    // Recognize Business API (api/v1/business/recognize.php)
    'error_invalid_business_id_provided' => 'Invalid or missing business ID.',
    'error_business_id_not_found_or_inactive' => 'The specified business could not be found or is not currently active.',
    'error_recognition_type_required' => 'Recognition type is required.', // Should not occur with default
    'error_recognition_type_too_long' => 'Recognition type value is too long (max 50 characters).',
    'error_comment_too_long' => 'Your comment exceeds the maximum length of 1000 characters.',
    'error_business_already_recognized' => 'You have already recognized this business. Thank you for your support!',
    'error_business_already_recognized_concurrent' => "It appears you've just recognized this business, or another recognition was processed simultaneously. Thanks!",
    'success_business_recognized' => 'Thank you for recognizing this business! Your feedback helps our community.',

    // File Upload Function (handle_file_upload in functions.php)
    'error_upload_no_file_input_name' => 'File input configuration error on server.',
    'error_upload_err_ini_size' => 'The uploaded file exceeds the maximum allowed file size set on the server.',
    'error_upload_err_form_size' => 'The uploaded file exceeds the maximum file size specified in the form.',
    'error_upload_err_partial' => 'The file was only partially uploaded. Please try again.',
    'error_upload_err_no_file' => 'No file was uploaded, or the file field was empty.',
    'error_upload_err_no_tmp_dir' => 'Server configuration error: Missing a temporary folder for uploads.',
    'error_upload_err_cant_write' => 'Server error: Failed to write the uploaded file to disk.',
    'error_upload_err_extension' => 'A PHP extension stopped the file upload. Please contact support.',
    'error_upload_unknown' => 'An unknown error occurred during file upload.',
    'error_upload_processing_not_implemented' => 'File upload processing is not yet fully implemented.',
    'success_upload_file_saved' => 'File uploaded successfully.', // For future use
    'error_upload_move_failed' => 'Could not save the uploaded file.', // For future use
    'error_upload_invalid_type' => 'Invalid file type. Allowed types are: %s.', // For future use, %s for allowed types
    'error_upload_too_large' => 'File is too large. Maximum allowed size is %s MB.', // For future use, %s for size

    // Add more translations as features are developed...
];
