<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit;
$config = require __DIR__.'/../../backend/bootstrap.php';
$db = Database::connection($config['db']);
foreach (['cover_image_url','subtitle','publish_place','edition','issn','languages','subjects','tags','table_of_contents'] as $column) {
    $db->exec("ALTER TABLE sach ADD COLUMN IF NOT EXISTS $column TEXT NULL");
}
$db->exec("ALTER TABLE sach_tac_gia ADD COLUMN IF NOT EXISTS author_role VARCHAR(80) NOT NULL DEFAULT 'Tác giả'");
Input::query($db,'INSERT IGNORE INTO schema_migrations(version) VALUES (?)',['008_book_details']);
echo "Book details ready\n";
