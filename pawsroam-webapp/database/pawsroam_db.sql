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
    slug VARCHAR(255) UNIQUE,
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
