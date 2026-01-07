ALTER TABLE `users`
  DROP KEY `external_id`,
  DROP COLUMN `external_id`,
  MODIFY `public_id` CHAR(6) NOT NULL,
  ADD UNIQUE KEY `public_id` (`public_id`);
