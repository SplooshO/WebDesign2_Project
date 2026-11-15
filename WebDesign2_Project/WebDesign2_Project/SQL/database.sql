CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    password_hash VARCHAR(50) NOT NULL,
    role VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL
)
CREATE TABLE members (
    member_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    real_name VARCHAR(100) NOT NULL,
    gamertag VARCHAR(100) NOT NULL,
    email VARCHAR(50) NOT NULL,
    program VARCHAR(50),
    favorite_game VARCHAR(50),
    gaming_platform VARCHAR(50),
    join_date DATE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
)
CREATE TABLE events (
    event_id INT PRIMARY KEY AUTO_INCREMENT,
    event_name VARCHAR(100) NOT NULL,
    game_title VARCHAR(50),
    event_date DATE NOT NULL,
    location VARCHAR(100) NOT NULL,
    max_players INT NOT NULL
)
CREATE TABLE registrations (
    registration_id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    event_id INT NOT NULL,
    FOREIGN KEY (member_id) REFERENCES members(member_id),
    FOREIGN KEY (event_id) REFERENCES events(event_id)
)