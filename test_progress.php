<?php
echo "PHP Version: " . phpversion() . "\n";
echo "session.upload_progress.enabled: " . ini_get('session.upload_progress.enabled') . "\n";
echo "session.upload_progress.name: " . ini_get('session.upload_progress.name') . "\n";
echo "uploadprogress_get_info exists: " . (function_exists('uploadprogress_get_info') ? 'Yes' : 'No') . "\n";
?>
