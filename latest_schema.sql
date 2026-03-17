-- ====== Base schema for all company databases ======

CREATE TABLE IF NOT EXISTS companyInfo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    domain VARCHAR(255) NOT NULL,
    name VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(50),
    address TEXT,
    logo VARCHAR(255),
    header_logo VARCHAR(255),
    banner_image VARCHAR(255),
    banner_caption VARCHAR(255),
    about_title VARCHAR(255),
    about_image VARCHAR(255),
    about_description TEXT,
    features_title VARCHAR(255),
    provide_title VARCHAR(255),
    provide_text TEXT,
    meta_title VARCHAR(255),
    meta_description TEXT,
    googletranslate VARCHAR(255),
    language_id INT DEFAULT 1,
    gallery_title VARCHAR(255),
    video_title VARCHAR(255),
    blog_title VARCHAR(255),
    blog_sub_title VARCHAR(255),
    pdf_title VARCHAR(255),
    googleincludedlanguages TEXT,
    header_script TEXT,
    body_script TEXT,
    show_ecommerce VARCHAR(10) DEFAULT 'on'
);

CREATE TABLE IF NOT EXISTS companyFeatures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT,
    title VARCHAR(255),
    description TEXT,
    icon VARCHAR(255),
    language_id INT DEFAULT 1
);

CREATE TABLE IF NOT EXISTS companyProvides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT,
    title VARCHAR(255),
    description TEXT,
    icon VARCHAR(255),
    language_id INT DEFAULT 1
);

CREATE TABLE IF NOT EXISTS companyGallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT,
    image_path VARCHAR(255),
    caption TEXT DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS companySocials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    name VARCHAR(50),
    icon_path VARCHAR(255),
    link_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS blogs (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    language_id INT(11) NOT NULL DEFAULT 1,
    category_id INT(11) DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    author VARCHAR(100) DEFAULT NULL,
    status ENUM('draft','published') DEFAULT 'draft',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS blogCategories (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    language_id INT(11) NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS companySections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_key VARCHAR(50) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

CREATE TABLE IF NOT EXISTS companyVideo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('link', 'file') DEFAULT 'link',
    video_link VARCHAR(255) DEFAULT NULL,
    video_file VARCHAR(255) DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    date DATE NOT NULL DEFAULT CURRENT_DATE
);

CREATE TABLE IF NOT EXISTS companyBanner (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS companyLanguages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    language VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS companyNavigation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nav_name VARCHAR(100) NOT NULL,
    nav_value VARCHAR(100) NOT NULL,
    language_id INT NOT NULL
);

CREATE TABLE IF NOT EXISTS companySubnav (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT,
    subnav_name VARCHAR(100) NOT NULL,
    subnav_link VARCHAR(100),
    language_id INT NOT NULL
);

CREATE TABLE IF NOT EXISTS companyCarousel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT,
    section VARCHAR(255) DEFAULT NULL,
    title VARCHAR(255) DEFAULT NULL,
    language_id INT NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS companyCarouselSlides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    carousel_id INT DEFAULT NULL,
    slide_id INT DEFAULT NULL,
    title VARCHAR(255) DEFAULT NULL,
    icon VARCHAR(255) DEFAULT NULL,
    text TEXT DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS companyPDFs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT,
    title VARCHAR(255) NOT NULL,
    pdf_file VARCHAR(255) NOT NULL,
    language_id INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
