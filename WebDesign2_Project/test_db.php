<?php
require 'db.php';
echo 'connected, users in table:'.$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
