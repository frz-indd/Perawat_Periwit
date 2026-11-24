-- Migrasi konten halaman Tentang Explore MajaKu
-- Aman dijalankan pada database eksplormajaku.
CREATE TABLE IF NOT EXISTS about_content (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    section_key VARCHAR(40) NOT NULL,
    title VARCHAR(150) NOT NULL,
    content TEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_about_content_section (section_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO about_content (section_key, title, content, sort_order, is_active) VALUES
('intro', 'Tentang Kami', 'Explore MajaKu adalah platform informasi wisata yang membantu pengguna menemukan dan mengenal berbagai destinasi wisata di Majalengka dan Kuningan. Kami menyediakan informasi destinasi, lokasi, fasilitas, harga tiket, jam operasional, dan ulasan untuk memudahkan wisatawan.', 1, 1),
('vision', 'Visi', 'Menjadi platform wisata terpercaya yang mendukung pengembangan pariwisata wilayah Majalengka dan Kuningan.', 2, 1),
('mission', 'Misi', 'Menyediakan informasi wisata yang akurat dan terkini. Memudahkan wisatawan dalam merencanakan perjalanan.', 3, 1)
ON DUPLICATE KEY UPDATE section_key = VALUES(section_key);
