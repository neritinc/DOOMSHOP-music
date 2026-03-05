CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role INT NOT NULL,
  phone VARCHAR(50),
  city VARCHAR(120),
  street VARCHAR(120),
  house_number VARCHAR(30),
  zip_code VARCHAR(20),
  billing_phone VARCHAR(50),
  billing_city VARCHAR(120),
  billing_street VARCHAR(120),
  billing_house_number VARCHAR(30),
  billing_zip_code VARCHAR(20)
);

CREATE TABLE genres (
  genre_id INT AUTO_INCREMENT PRIMARY KEY,
  genre_name VARCHAR(255) NOT NULL
);

CREATE TABLE artists (
  artist_id INT AUTO_INCREMENT PRIMARY KEY,
  artist_name VARCHAR(255) NOT NULL,
  artist_picture VARCHAR(255)
);

CREATE TABLE tracks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  genre_id INT NOT NULL,
  track_title VARCHAR(255) NOT NULL,
  bpm_value INT,
  release_date DATE,
  track_length_sec INT,
  track_cover VARCHAR(255),
  track_path VARCHAR(255),
  CONSTRAINT fk_tracks_genres FOREIGN KEY (genre_id) REFERENCES genres(genre_id)
);

CREATE TABLE track_artists (
  track_id INT NOT NULL,
  artist_id INT NOT NULL,
  PRIMARY KEY (track_id, artist_id),
  CONSTRAINT fk_track_artists_track FOREIGN KEY (track_id) REFERENCES tracks(id),
  CONSTRAINT fk_track_artists_artist FOREIGN KEY (artist_id) REFERENCES artists(artist_id)
);

CREATE TABLE carts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  date DATE NOT NULL,
  CONSTRAINT fk_carts_users FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE cart_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cart_id INT NOT NULL,
  track_id INT NOT NULL,
  pcs INT NOT NULL,
  CONSTRAINT fk_cart_items_carts FOREIGN KEY (cart_id) REFERENCES carts(id),
  CONSTRAINT fk_cart_items_tracks FOREIGN KEY (track_id) REFERENCES tracks(id)
);

INSERT INTO users (
  name,
  email,
  password,
  role,
  phone,
  city,
  street,
  house_number,
  zip_code,
  billing_phone,
  billing_city,
  billing_street,
  billing_house_number,
  billing_zip_code
) VALUES
(
  'Admin User',
  'admin@doomshoprecords.com',
  'admin123',
  1,
  '+1-555-0100',
  'Los Angeles',
  'Sunset Blvd',
  '100',
  '90001',
  '+1-555-0100',
  'Los Angeles',
  'Sunset Blvd',
  '100',
  '90001'
),
(
  'Customer User',
  'customer@doomshoprecords.com',
  'customer123',
  2,
  '+1-555-0101',
  'New York',
  'Broadway',
  '200',
  '10001',
  '+1-555-0101',
  'New York',
  'Broadway',
  '200',
  '10001'
);
