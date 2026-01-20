<?php include 'fonk.php'; \ = local_database(); \ = \->query('SHOW TABLES LIKE \
%oner%\;'); if (\) { while(\ = \->fetch_row()) echo \[0] . PHP_EOL; } else { echo 'SQL Error: ' . \->error; } ?>
