--
-- Table structure for table `orders`
--
-- This SQL script creates the 'orders' table to store customer order information.
-- It includes an auto-incrementing primary key, foreign key to productseller, and other relevant fields.

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `date_time` datetime NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `product_type` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`);

--
-- AUTO_INCREMENT for dumped tables
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
COMMIT;
