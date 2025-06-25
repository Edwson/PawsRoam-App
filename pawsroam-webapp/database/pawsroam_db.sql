-- Users with role system
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'business_admin', 'user') DEFAULT 'user',
    language_preference VARCHAR(5) DEFAULT 'en',
    timezone VARCHAR(50) DEFAULT 'UTC',
    status ENUM('active', 'pending', 'suspended') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Keep track of updates
    -- For "Remember Me" functionality
    remember_token_hash VARCHAR(255) NULL DEFAULT NULL,
    remember_token_expires_at TIMESTAMP NULL DEFAULT NULL
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Businesses with detailed pet policies
CREATE TABLE businesses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    owner_user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL, -- Added: Default/primary business name
    slug VARCHAR(255) UNIQUE,
    description TEXT NULL DEFAULT NULL, -- Added: Default short description
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    pawstar_rating TINYINT(1) DEFAULT 0,
    total_recognitions INT DEFAULT 0,
    allows_off_leash BOOLEAN DEFAULT FALSE,
    has_water_bowls BOOLEAN DEFAULT FALSE,
    has_pet_menu BOOLEAN DEFAULT FALSE,
    weight_limit_kg DECIMAL(5,2),
    pet_size_limit ENUM('small','medium','large','any') DEFAULT 'any',
    status ENUM('pending','active','inactive') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_user_id) REFERENCES users(id)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Universal translation system
CREATE TABLE translations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    translatable_type VARCHAR(50) NOT NULL,
    translatable_id INT NOT NULL,
    field_name VARCHAR(50) NOT NULL,
    language_code VARCHAR(5) NOT NULL,
    content TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    is_machine_translated BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_translatable (translatable_type, translatable_id, language_code)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- PawsSafe providers
CREATE TABLE pawssafe_providers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    service_types JSON, -- ['sitting','walking','boarding','daycare']
    hourly_rate_usd DECIMAL(8,2),
    availability_schedule JSON,
    certifications TEXT,
    insurance_verified BOOLEAN DEFAULT FALSE,
    background_check_status ENUM('pending','approved','rejected') DEFAULT 'pending',
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_bookings INT DEFAULT 0,
    status ENUM('pending','active','suspended') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Business Recognitions (for PawStar system)
CREATE TABLE business_recognitions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    business_id INT NOT NULL,
    recognition_type VARCHAR(50) DEFAULT 'general', -- e.g., 'general', 'amenity_highlight', 'service_excellence'
    comment TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    UNIQUE KEY uk_user_business_recognition (user_id, business_id) -- Prevents a user from giving multiple 'general' recognitions to the same business. If different types are distinct, type should be in key.
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Business Reviews
CREATE TABLE business_reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    business_id INT NOT NULL,
    rating TINYINT(1) NOT NULL COMMENT 'User rating from 1 to 5 stars',
    title VARCHAR(255) NULL DEFAULT NULL,
    comment TEXT NULL DEFAULT NULL,
    review_photos JSON NULL DEFAULT NULL COMMENT 'Array of image paths related to the review',
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    UNIQUE KEY uk_user_business_review (user_id, business_id) -- One review per user per business
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Modify businesses table to add review aggregate columns (for future calculation)
-- ALTER TABLE businesses
-- ADD COLUMN average_review_rating DECIMAL(3,2) DEFAULT 0.00 AFTER pawstar_rating,
-- ADD COLUMN total_review_count INT DEFAULT 0 AFTER average_review_rating;


-- Pet profiles
CREATE TABLE user_pets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    species ENUM('dog','cat','bird','rabbit','other') NOT NULL,
    breed VARCHAR(100),
    size ENUM('small','medium','large','extra_large'),
    weight_kg DECIMAL(5,2),
    birthdate DATE,
    personality_traits JSON, -- ['anxious','friendly','energetic']
    medical_conditions JSON,
    dietary_restrictions JSON,
    emergency_contacts JSON,
    avatar_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
