#!/usr/bin/env php
<?php require_once __DIR__ . '/../../setup/create_table.sql'; ?>
BEGIN TRANSACTION;
<?php require_once __DIR__ . '/./main/main.php'; ?>
COMMIT;
