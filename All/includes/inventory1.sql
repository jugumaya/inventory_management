CREATE TABLE inventory1 (
    id INT(11) NOT NULL AUTO_INCREMENT,
    meat_type VARCHAR(255) NOT NULL,
    batch_number VARCHAR(255) NOT NULL,
    quantity FLOAT NOT NULL,
    supplier VARCHAR(255) NOT NULL,
    cost FLOAT NOT NULL,
    -- This is the new column you need to add
    total_cost FLOAT NOT NULL, 
    processing_date DATE NOT NULL,
    expiration_date DATE NOT NULL,
    location VARCHAR(255) NOT NULL,
    PRIMARY KEY (id)
);
ALTER TABLE inventory1
ADD COLUMN total_cost FLOAT NOT NULL;
