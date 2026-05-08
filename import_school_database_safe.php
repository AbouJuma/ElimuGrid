<?php

echo "=== SAFE SCHOOL DATABASE IMPORT ===\n\n";

// This script helps you import the school database without table conflicts
// It will drop existing tables before importing to avoid "Table already exists" errors

echo "Instructions for importing school database safely:\n\n";

echo "1. BACKUP CURRENT DATABASE (IMPORTANT!)\n";
echo "   mysqldump -u root -p eschool_saas_3_baobab > backup_before_import.sql\n\n";

echo "2. DROP ALL TABLES IN SCHOOL DATABASE:\n";
echo "   mysql -u root -p eschool_saas_3_baobab\n";
echo "   DROP DATABASE eschool_saas_3_baobab;\n";
echo "   CREATE DATABASE eschool_saas_3_baobab;\n";
echo "   EXIT;\n\n";

echo "3. IMPORT SCHOOL DATABASE:\n";
echo "   mysql -u root -p eschool_saas_3_baobab < school_database.sql\n\n";

echo "4. RUN THE FIX SCRIPT:\n";
echo "   php fix_production_database.php\n\n";

echo "=== ALTERNATIVE: AUTOMATED SCRIPT ===\n";
echo "If you want to automate this, create a script like:\n\n";

echo "<?php\n";
echo "// Automated database import script\n";
echo "// WARNING: This will delete all existing data!\n\n";

echo "\$databaseName = 'eschool_saas_3_baobab';\n";
echo "\$sqlFile = 'school_database.sql';\n\n";

echo "// Connect to MySQL\n";
echo "\$mysqli = new mysqli('localhost', 'root', '', 'mysql');\n\n";

echo "// Drop and recreate database\n";
echo "\$mysqli->query('DROP DATABASE IF EXISTS ' . \$databaseName);\n";
echo "\$mysqli->query('CREATE DATABASE ' . \$databaseName);\n\n";

echo "// Import SQL file\n";
echo "\$mysqli->select_db(\$databaseName);\n";
echo "\$sql = file_get_contents(\$sqlFile);\n";
echo "\$mysqli->multi_query(\$sql);\n\n";

echo "echo 'Database import complete!';\n";
echo "?>\n\n";

echo "=== AFTER IMPORT ===\n";
echo "After importing the school database, run:\n";
echo "php artisan cache:clear\n";
echo "php artisan config:clear\n";
echo "php artisan view:clear\n\n";

echo "Then test the Virtual Classroom live session again.\n";
