```php
<?php

session_start();

// Remove all session data
$_SESSION = [];

// Destroy the session
session_destroy();

// Go back to homepage as guest
header("Location: index.php");
exit();

?>
```
