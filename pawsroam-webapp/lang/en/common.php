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
    'tooltip_add_new_pet' => 'Add a new pet to your profile (Feature coming soon)', // Kept for reference if needed
    'tooltip_add_new_pet_now' => 'Add a new pet to your profile',
    'button_add_new_pet' => 'Add New Pet',
    'error_pet_profiles_load_failed_db' => 'Could not load your pet profiles due to a database error. Please try again.',
    'pet_profiles_list_title' => 'Your Registered Pets',
    'alt_text_no_pets_illustration' => 'Illustration of an empty pet carrier or a pet looking sad',
    'pet_profiles_none_found_message' => "You haven't added any pet profiles yet.",
    'button_add_your_first_pet_link_text' => 'add your first pet profile',
    'pet_profiles_add_one_prompt_html %s' => 'It looks a bit empty here! Why not %s and share details about your furry, feathery, or scaly friend?', // %s is for the link
    'tooltip_add_first_pet' => 'Add your first pet now!', // Updated from "coming soon"
    'button_add_your_first_pet' => 'Add Your First Pet', // Button text if not using the link version
    'pet_profiles_stub_note' => '(Pet listing and management functionality is currently a stub and will be implemented soon!)', // This can be removed or updated later
    'pet_avatar_alt %s' => '%s\'s avatar', // %s for pet name
    'tooltip_view_pet_profile %s' => "View %s's full profile", // Updated from generic "coming soon"
    'button_view' => 'View',
    'tooltip_edit_pet_profile %s' => "Edit %s's profile", // Updated from generic "coming soon"
    // 'button_edit' => 'Edit', // Already have a general 'button_edit'
    'tooltip_delete_pet_profile %s' => "Delete %s's profile", // Updated from generic "coming soon"
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

    // Forum Category View Page (pages/forums/category-view.php)
    'page_title_forum_category_default' => 'Forum Category',
    'error_forum_no_category_slug_provided' => 'No category specified to display.',
    'error_forum_category_not_found' => 'The requested forum category was not found.',
    'page_title_forum_category' => 'Forum Category', // Used as suffix: "[Category Name] - Forum Category"
    'button_back_to_forums_main' => 'Back to Main Forums',
    'breadcrumb_pawsconnect_home' => 'PawsConnect', // Or just "Forums"
    'tooltip_start_new_topic_in_category_soon %s' => 'Start a new discussion topic in %s (Feature coming soon)',
    'button_new_topic' => 'New Topic',
    'forum_category_no_topics_title' => 'No Topics Yet!',
    'forum_category_no_topics_message' => 'There are no topics in this category yet. Be the first to start a discussion!',
    'tooltip_be_the_first_to_post_topic_soon' => 'Be the first to post a topic in this category! (Feature coming soon)',
    'button_start_first_topic' => 'Start the First Topic',
    'topic_sticky_tooltip' => 'Sticky Topic',
    'topic_locked_tooltip' => 'Locked Topic (No new replies)',
    '%d views' => '%d views',
    'topic_started_by %s' => 'Started by %s', // %s for username
    '%d replies' => '%d replies',
    'topic_last_post_by %s' => 'Last post by %s', // %s for username
    'topic_no_replies_yet' => 'No replies yet',

    // New Topic Page (pages/forums/new-topic.php)
    'page_title_new_topic' => 'Start a New Discussion Topic',
    'error_forums_categories_load_failed' => 'Could not load forum categories. Please try again or contact support.',
    'error_forums_no_categories_to_post_in' => 'There are currently no categories available to post a new topic in. Please check back later.',
    'new_topic_form_title' => 'Create Your Discussion Topic',
    'new_topic_select_category_placeholder' => '-- Select a Category for Your Topic --',
    'new_topic_label_category' => 'Category',
    'new_topic_placeholder_title' => 'Enter a clear and descriptive title for your topic',
    'new_topic_label_title' => 'Topic Title',
    'new_topic_placeholder_content' => "Share your thoughts, ask your question, or start the discussion here...\n\nTip: You can use basic Markdown for formatting (e.g., **bold**, *italics*, lists).",
    'new_topic_label_content' => 'Your Message (This will be the first post)',
    'button_create_topic' => 'Create Topic & Post',
    'new_topic_error_unknown' => 'An unknown error occurred while trying to create your topic. Please try again.',
    'new_topic_error_network' => 'A network error occurred. Please check your connection and try creating your topic again.',
    'tooltip_start_new_discussion' => 'Start a new discussion topic', // Updated from _soon
    'tooltip_start_new_topic_in_category %s' => 'Start a new topic in the %s category', // Updated from _soon
    'tooltip_be_the_first_to_post_topic' => 'Be the first to post a topic in this category!', // Updated from _soon


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

    // Edit Pet Page (pages/pets/edit-pet.php)
    'page_title_edit_pet' => 'Edit Pet Profile',
    'page_title_edit_pet_name %s' => 'Edit Profile for %s', // %s is pet name
    'error_invalid_pet_id_for_edit' => 'No pet specified or invalid ID for editing.',
    'error_pet_not_found_or_not_owned' => 'Pet profile not found or you do not have permission to edit it.',
    'edit_pet_form_title' => "Update Your Pet's Details",
    'edit_pet_section_avatar' => 'Update Profile Picture',
    'label_pet_avatar_new' => 'Upload New Avatar (Optional)',
    'edit_pet_current_avatar_label' => 'Current Avatar:',
    'edit_pet_remove_avatar_checkbox_label' => 'Remove current avatar (will be replaced if new avatar is uploaded)',
    'alt_new_pet_avatar_preview' => 'New Avatar Preview',
    'button_update_pet_submit' => 'Update Pet Profile',
    'edit_pet_alert_success' => 'Pet profile updated successfully!',
    'edit_pet_alert_failed_unknown' => 'Failed to update pet profile. Please check the form for errors.',
    'edit_pet_alert_failed_network' => 'A network error occurred while updating the pet profile. Please try again.',

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

    // Delete Pet JS Alerts (pages/pet-profile.php)
    'success_pet_profile_deleted_js_alert' => 'Pet profile deleted successfully.',
    'error_pet_delete_failed_js_alert' => 'Failed to delete pet profile. Please try again.',
    'error_pet_delete_network_js_alert' => 'An error occurred while trying to delete the pet profile. Please check your connection and try again.',

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

    // Delete Pet API (api/v1/pets/delete.php)
    'error_invalid_pet_id_provided' => 'Invalid or missing pet ID for deletion.',
    'error_pet_not_found_for_deletion' => 'The selected pet profile could not be found.',
    'error_pet_delete_unauthorized' => 'You are not authorized to delete this pet profile.',
    'error_pet_delete_failed_db' => 'Failed to delete the pet profile from the database. Please try again.',
    'success_pet_avatar_deleted' => 'The associated avatar file was also deleted.',
    'error_pet_avatar_delete_failed' => 'The pet profile was deleted, but the associated avatar file could not be removed from the server. Please contact support if this persists.',
    'info_pet_avatar_not_found_on_disk' => 'The pet profile was deleted. No associated avatar file was found on disk to remove.',
    'success_pet_profile_deleted' => 'Pet profile deleted successfully.',
    'success_pet_profile_deleted_js_alert' => 'Pet profile deleted successfully.', // For JS alert
    'error_pet_delete_failed_js_alert' => 'Failed to delete pet profile. Please try again.', // For JS alert
    'error_pet_delete_network_js_alert' => 'An error occurred while trying to delete the pet profile. Please check your connection and try again.', // For JS alert


    // Recognize Business Button (pages/business-detail.php)
    'tooltip_login_to_recognize' => 'You must be logged in to recognize this place.',
    'tooltip_recognize_this_place' => 'Add your recognition for this place',
    'recognize_this_place_button' => 'Recognize this Place',
    'recognize_success_message_short' => 'Successfully recognized!',
    'recognize_button_recognized_text' => 'Recognized!',
    'recognize_failed_message_short' => 'Recognition failed.',
    'recognize_button_already_recognized_text' => 'Already Recognized',
    'recognize_failed_network' => 'A network error occurred. Please try again.',
    'tooltip_already_recognized' => "You've already recognized this place. Thank you!",

    // Edit Pet API (api/v1/pets/update.php)
    'error_invalid_pet_id_for_edit_api' => 'Invalid pet ID specified for update.',
    'error_pet_not_found_or_not_owned_api' => 'Pet profile not found, or you are not authorized to edit it.',
    'edit_pet_no_changes_detected' => 'No changes were detected in the pet profile information.',
    'edit_pet_api_success' => 'Pet profile updated successfully!',
    'edit_pet_api_failed_db' => 'Failed to update pet profile due to a database error. Please try again.',
    // Validation errors (e.g., error_pet_name_required) are often reused from Add Pet API

    // Admin Review Management Page (pages/admin/reviews.php)
    'page_title_admin_reviews' => 'Review Management - Admin',
    'admin_reviews_description' => 'Moderate user-submitted reviews for businesses. Approve, reject, or edit reviews.',
    'admin_reviews_filter_title' => 'Filter Reviews',
    'admin_reviews_filter_status_label' => 'Status',
    'admin_reviews_filter_status_all' => 'All Statuses',
    'admin_reviews_filter_status_pending' => 'Pending Approval',
    'admin_reviews_filter_status_approved' => 'Approved',
    'admin_reviews_filter_status_rejected' => 'Rejected',
    'admin_reviews_filter_business_label' => 'Business Name',
    'admin_reviews_filter_business_placeholder' => 'Filter by Business Name...',
    'admin_reviews_filter_button_tooltip' => 'Filtering functionality is not yet implemented.',
    // 'button_filter' => 'Filter', // Reusable
    'admin_reviews_list_title_pending' => 'Pending Reviews', // Example title, will be dynamic
    'admin_reviews_placeholder_text' => 'A list of reviews with actions to approve/reject will appear here. This functionality is currently a stub.',
    'admin_reviews_pagination_placeholder' => 'Pagination for reviews will appear here.',
    'admin_link_manage_reviews_tooltip' => 'Moderate and manage user reviews', // For dashboard link
    'admin_link_manage_reviews' => 'Manage Reviews', // For dashboard link
    'admin_link_manage_reviews_desc' => 'Approve, reject, or edit user-submitted reviews.', // For dashboard link


    // View Pet Page (pages/pets/view-pet.php)
    'page_title_view_pet_default' => 'View Pet Profile',
    'error_invalid_pet_id_for_view' => 'No pet specified or invalid ID for viewing.',
    'error_pet_not_found_or_not_owned_view' => 'Pet profile not found or you do not have permission to view it.',
    'page_title_view_pet_name %s' => 'Viewing Profile for %s', // %s is pet name
    'button_edit_this_pet' => 'Edit This Pet',
    'view_pet_section_details' => 'Pet Details',
    'pet_age_years_months %d %d' => 'Approx. %d years, %d months old', // %d for years, %d for months

    // Business Review API Stubs (api/v1/reviews/create.php & list.php)
    'error_review_invalid_business_id' => 'A valid business ID is required to submit a review.',
    'error_review_invalid_rating' => 'Please provide a rating between 1 and 5 stars.',
    'error_review_title_too_long' => 'Review title cannot exceed 255 characters.',
    'error_review_comment_too_short' => 'Your review comment must be at least 10 characters long.',
    'error_review_comment_too_long' => 'Your review comment cannot exceed 5000 characters.',
    'error_review_already_submitted' => 'You have already submitted a review for this business. You may be able to edit your existing review.',
    'success_review_submitted_pending' => 'Your review has been submitted successfully and is now pending approval. Thank you for your feedback!',
    'error_review_invalid_business_id_list' => 'A valid business ID is required to list reviews.',
    'error_review_business_not_found_or_inactive' => 'The business you are trying to review cannot be found or is not currently active.',
    'error_review_comment_too_short_if_provided' => 'If you provide a comment, it must be at least 10 characters long.',
    'error_review_title_or_comment_required' => 'Please provide either a review title or a comment.',

    // Admin Review Management API Stubs (api/v1/admin/reviews/*)
    'error_admin_invalid_review_status_filter' => 'Invalid status filter provided for reviews.',
    'error_admin_invalid_review_id_for_update' => 'Invalid review ID provided for status update.',
    'error_admin_invalid_new_status_for_review' => 'Invalid new status provided for the review. Must be "approved" or "rejected".',
    'success_admin_review_status_updated %s %s' => 'Review ID %s status has been updated to %s.', // %s for review_id, %s for new_status
    'error_admin_review_not_found_or_no_change' => 'Review not found, or its status was already set to the new value.',
    'info_admin_review_status_already_set %s' => 'Review status is already %s.', // %s is status

    // Admin Review Management Page (pages/admin/reviews.php - Frontend)
    'admin_reviews_list_title_filtered' => 'Filtered Reviews',
    'admin_review_col_id' => 'ID',
    'admin_review_col_business' => 'Business',
    'admin_review_col_author' => 'Author',
    'admin_review_col_rating' => 'Rating',
    'admin_review_col_comment' => 'Comment',
    'admin_review_col_status' => 'Status',
    'admin_review_col_date' => 'Date',
    'admin_review_col_actions' => 'Actions',
    'admin_reviews_loading_initial' => 'Loading reviews...',
    'admin_reviews_none_found_with_filters' => 'No reviews found matching your current filter criteria.',
    'admin_reviews_load_error' => 'An error occurred while trying to load reviews.',
    'admin_reviews_load_network_error' => 'A network error occurred while loading reviews. Please try again.',
    'admin_review_action_approve' => 'Approve Review',
    'admin_review_action_reject' => 'Reject Review',
    'admin_review_action_approve_rejected' => 'Approve (Rejected Review)',
    'admin_review_action_confirm %s %s' => 'Are you sure you want to %s review ID %s?', // %s action, %s reviewId
    'admin_review_action_failed %s' => 'Failed to %s review.', // %s action
    'admin_review_action_network_error %s' => 'Network error trying to %s review.', // %s action


    // User Avatar Upload (pages/profile.php JS and api/v1/user/avatar-upload.php)
    'profile_button_select_avatar' => 'Select New Avatar',
    'profile_button_upload_avatar' => 'Upload Avatar',
    'profile_error_no_avatar_selected' => 'Please select an image file to upload for your avatar.',
    'state_text_uploading' => 'Uploading...', // Generic, can be reused
    'profile_success_avatar_uploaded' => 'Avatar uploaded successfully! Your profile picture has been updated.',
    'profile_error_avatar_upload_failed' => 'Avatar upload failed. Please ensure the file is a valid image (JPG, PNG) and within size limits.',
    'profile_error_avatar_upload_network' => 'A network error occurred during avatar upload. Please try again.',
    'error_avatar_upload_auth_failed' => 'Avatar upload authorization failed. Please try logging in again.',
    'profile_success_avatar_uploaded_api' => 'Avatar uploaded and updated successfully!', // API specific success
    'profile_error_avatar_db_update_failed' => 'Failed to update profile with the new avatar due to a database error.',

    // Review Submission Form (pages/business-detail.php)
    'review_already_submitted_title' => "You've Reviewed This Place!",
    'review_your_rating_text %s' => "Your rating: %s stars.", // %s is rating number
    'review_status_pending_message' => "Your review is currently pending approval by our team.",
    'review_status_approved_message' => "Your review is live! Thank you for sharing.",
    'review_edit_functionality_soon_text' => "(Functionality to edit your review will be available soon.)",
    'write_your_review_title' => "Share Your Experience",
    'review_rating_label' => "Your Overall Rating:",
    '%d stars' => '%d stars', // For star rating titles e.g., "5 stars"
    'review_title_placeholder' => "e.g., Best dog park ever!",
    'review_title_label' => "Review Title (Optional)",
    'review_comment_placeholder' => "Tell us about your experience, what you liked, and any tips for other pet owners...",
    'review_comment_label' => "Your Review / Comment",
    'review_submit_button' => "Submit Review",
    'review_login_link_text' => "Log in",
    'review_register_link_text' => "create an account",
    'review_login_prompt %s or %s' => "%s or %s to share your experience!", // %s are links
    'reviews_loading_placeholder' => "(Loading reviews...)", // For the review list area
    'review_submitted_thank_you_title' => "Thank You!", // JS success message title
    // 'success_review_submitted_pending' is already defined for API, can be reused for JS
    'review_submit_failed_unknown' => "Failed to submit review. Please check the form and try again.", // JS general error
    'error_validation_summary' => "Please correct the following errors:", // For JS if multiple errors from API
    'review_submit_failed_network' => "A network error occurred while submitting your review. Please try again.", // JS network error
    'review_status_rejected_message' => "Your previous review for this place was not approved.", // For display if existing review is rejected

    // Average Review Rating Display (pages/business-detail.php)
    'average_rating_from_reviews_title %s %s' => 'Average rating of %s out of 5 stars from %s user reviews.', // %s for avg rating, %s for count
    'review_singular' => 'review',
    'review_plural' => 'reviews',
    'no_reviews_yet_short' => 'No user reviews yet',

    // Display Reviews (pages/business-detail.php JS)
    'button_load_more_reviews' => 'Load More Reviews',
    'reviews_none_found_message' => 'No reviews yet for this place. Be the first to share your experience!',
    'reviews_load_failed_network' => 'Could not load reviews due to a network error. Please try refreshing the page.',
    '%d/3 PawStars' => '%d/3 PawStars', // For PawStar title attribute (e.g., "2/3 PawStars")
    // '%d stars' is already defined for rating input, can be reused for display if it's 1-5 for reviews.
    // Let's add a specific one for review display if needed:
    'review_rating_display %d/5' => '%d/5 Stars', // For displayed review rating, e.g. "4/5 Stars"

    // PawsConnect Main Page (pages/pawsconnect.php)
    'page_title_pawsconnect_main' => 'PawsConnect Community Forums',
    'error_forums_load_failed_db' => 'Could not load forum categories due to a database error. Please try again later.',
    'error_forums_not_setup_yet' => 'Our community forums are currently under construction. Please check back soon!',
    'tooltip_start_new_discussion_soon' => 'Start a new discussion topic (Feature coming soon)',
    'button_start_new_discussion' => 'Start New Discussion',
    'button_login_to_participate' => 'Login to Participate',
    'forums_no_categories_title' => 'No Forum Categories Yet!',
    'forums_no_categories_message' => 'It seems there are no discussion categories available at the moment. Please check back later or contact an administrator if you believe this is an error.',
    'forums_welcome_intro' => 'Welcome to the PawsConnect community! Find discussions, share advice, and connect with other pet lovers.',
    '%d topics / %d posts' => '%d topics / %d posts', // For category stats (future)
    'forum_category_no_description' => 'No description available for this category.',

    // Forum Topic View Page (pages/forums/topic-view.php)
    'page_title_forum_topic_default' => 'View Topic',
    'error_forum_no_topic_slug_provided' => 'No topic specified to display.',
    'error_forum_topic_not_found' => 'The requested discussion topic was not found.',
    'error_forums_topic_load_failed_db' => 'Could not load the topic details due to a database error.',
    'tooltip_reply_to_topic_soon' => 'Reply to this topic (Feature coming soon)',
    'button_reply_to_topic' => 'Reply to Topic',
    'topic_is_locked_message' => 'This topic is locked. No new replies can be posted.',
    'forum_topic_no_posts_yet' => 'This topic has no posts yet.',
    'forum_topic_be_first_to_reply' => 'Be the first to reply!',
    'forum_topic_reply_form_title' => 'Post a Reply',
    'forum_topic_locked_cannot_reply' => 'This topic is locked. No new replies can be posted.',
    'forum_topic_reply_placeholder' => 'Enter your reply...',
    'forum_topic_markdown_supported_note' => 'Basic Markdown is supported. (Feature coming soon)',
    'button_submit_reply' => 'Submit Reply',
    'forum_reply_feature_stub_note' => '(Reply functionality is a stub for now)',
    'forum_topic_login_to_reply %s' => '%s to post a reply.', // %s is login link
    'forum_post_by %s' => 'Posted by %s', // %s is username. For general posts.
    'forum_post_edited_at %s' => 'edited %s', // %s is time ago, e.g., "edited 5 minutes ago"
    'tooltip_edit_this_post' => 'Edit this post',
    'button_edit_post_short' => 'Edit',

    // Edit Post Page (pages/forums/edit-post.php)
    'page_title_edit_post_default' => 'Edit Post',
    'page_title_edit_post_dynamic' => 'Editing Post #%s', // %s is post ID
    'error_invalid_post_id_for_edit' => 'Invalid post ID specified for editing.',
    'error_post_edit_not_owner' => 'You do not have permission to edit this post.',
    'error_post_edit_topic_locked' => 'This topic is locked, so posts cannot be edited.',
    'error_post_edit_time_limit_exceeded' => 'The time limit for editing this post has passed.', // If implemented
    'error_post_not_found_or_deleted' => 'The post you are trying to edit could not be found or has been deleted.',
    'edit_post_label_content' => 'Your Post Content',
    'button_save_changes' => 'Save Changes',
    'error_cannot_edit_post_generic' => 'This post cannot be edited at this time.',
    'button_back_to_topic_or_forums' => 'Back to Topic / Forums',
    'success_post_updated_redirecting' => 'Post updated successfully! Redirecting you back to the topic...',
    'error_post_update_failed_unknown' => 'Failed to update post. Please check your input and try again.',
    'error_post_update_failed_network' => 'A network error occurred while updating the post. Please try again.',

    // Edit Post API (api/v1/forums/posts/update.php)
    'error_invalid_post_id_for_edit_api' => 'Invalid post ID provided for update.',
    'error_post_content_required' => 'Post content cannot be empty.',
    'error_post_content_min_length_detailed' => 'Post content must be at least %d characters long.', // %d for min length
    'error_post_content_max_length_detailed' => 'Post content cannot exceed %d characters.', // %d for max length
    'error_post_not_found_or_deleted_api' => 'Post not found or it may have been deleted.',
    'error_post_edit_not_owner_api' => 'You are not authorized to edit this post.',
    'error_post_edit_topic_locked_api' => 'Cannot edit post because the topic is locked.',
    'error_post_edit_time_limit_exceeded_api' => 'The time window for editing this post has expired.', // If implemented
    'success_post_updated' => 'Post updated successfully.',
    'error_post_update_no_change_or_failed' => 'No changes were made to the post, or the update failed unexpectedly.',
    'error_post_update_failed_db' => 'Failed to update post due to a database error. Please try again later.',

    // Delete Post Functionality (API & Frontend)
    'tooltip_delete_this_post' => 'Delete this post',
    'button_delete_post_short' => 'Delete',
    'modal_title_delete_post_confirm' => 'Confirm Post Deletion',
    'modal_body_delete_post_warning' => 'Are you sure you want to delete this post? This action cannot be undone. If this is the first post of a topic, the entire topic will be deleted.',
    'button_delete_confirm_post' => 'Yes, Delete Post',
    'error_invalid_post_id_for_delete_api' => 'Invalid post ID provided for deletion.',
    'error_post_not_found_or_already_deleted_api' => 'Post not found or it has already been deleted.',
    'error_post_delete_not_owner_api' => 'You are not authorized to delete this post.',
    'error_post_delete_topic_locked_api' => 'Cannot delete post because the topic is locked.',
    'error_post_delete_failed_db' => 'Failed to delete post due to a database error.',
    'success_topic_and_first_post_deleted' => 'The topic (including this first post) has been successfully deleted.',
    'success_post_deleted' => 'Post successfully deleted.',
    'success_topic_and_first_post_deleted_js' => 'Topic and its first post have been deleted.',
    'success_post_deleted_js' => 'Post deleted successfully.',
    'text_post_deleted_placeholder' => '[This post has been deleted]',
    'error_post_delete_failed_js' => 'Failed to delete post. Please try again.',
    'error_post_delete_network_js' => 'A network error occurred while trying to delete the post. Please try again.',

    // Delete Topic Functionality (API & Frontend)
    'tooltip_delete_this_topic' => 'Delete this entire topic',
    'button_delete_topic_short' => 'Delete Topic',
    'modal_title_delete_topic_confirm' => 'Confirm Topic Deletion',
    'modal_body_delete_topic_warning' => 'Are you sure you want to delete this entire topic? This action cannot be undone and will remove all posts within it.',
    'button_delete_confirm_topic' => 'Yes, Delete Topic',
    'error_invalid_topic_id_for_delete_api' => 'Invalid topic ID provided for deletion.',
    'error_topic_not_found_or_already_deleted_api' => 'Topic not found or it has already been deleted.',
    'error_topic_delete_not_owner_api' => 'You are not authorized to delete this topic.',
    'error_topic_delete_locked_by_admin_api' => 'This topic has been locked by an administrator and cannot be deleted by users.',
    'error_topic_delete_failed_db' => 'Failed to delete topic due to a database error.',
    'success_topic_deleted' => 'Topic successfully deleted.',
    'success_topic_deleted_js' => 'Topic has been deleted successfully.',
    'error_topic_delete_failed_js' => 'Failed to delete topic. Please try again.',
    'error_topic_delete_network_js' => 'A network error occurred while trying to delete the topic. Please try again.',

    // Admin Topic Locking/Unlocking
    'tooltip_lock_this_topic' => 'Lock this topic (prevents new replies and edits)',
    'tooltip_unlock_this_topic' => 'Unlock this topic (allows new replies and edits)',
    'button_lock_topic_short' => 'Lock Topic',
    'button_unlock_topic_short' => 'Unlock Topic',
    'error_invalid_topic_id_for_lock_api' => 'Invalid topic ID provided for lock/unlock operation.',
    'success_topic_locked' => 'Topic has been successfully locked.',
    'success_topic_unlocked' => 'Topic has been successfully unlocked.',
    'error_topic_lock_status_no_change' => 'Topic lock status was not changed (already in desired state or issue).',
    'error_topic_lock_update_failed_db' => 'Failed to update topic lock status due to a database error.',
    'error_topic_lock_toggle_failed_js' => 'Failed to toggle topic lock status.',
    'error_topic_lock_toggle_network_js' => 'A network error occurred while trying to toggle topic lock status.',
    'tooltip_reply_to_topic' => 'Post a reply to this topic', // Updated from _soon

    // Admin Dashboard
    'admin_link_forum_moderation' => 'Forum Moderation',
    'admin_link_forum_moderation_desc' => 'Manage forum categories, topics, posts, and moderate content.',

    // Add more translations as features are developed...
];
