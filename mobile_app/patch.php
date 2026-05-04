<?php
$file = '../app/Services/Email/EmailDispatcher.php';
$content = file_get_contents($file);

$content = str_replace(
    "            // Ensure recipient is a string and not too long for the DB\n" .
    "            \$recipient = is_array(\$to) ? json_encode(\$to) : (string) \$to;\n" .
    "            if (strlen(\$recipient) > 255) {\n" .
    "                \$recipient = substr(\$recipient, 0, 252).'...';\n" .
    "            }",
    "            // Ensure recipient is a string\n" .
    "            \$recipient = is_array(\$to) ? json_encode(\$to) : (string) \$to;\n" .
    "            \n" .
    "            // The DB column is now TEXT (65535 chars)\n" .
    "            if (strlen(\$recipient) > 65000) {\n" .
    "                \$recipient = substr(\$recipient, 0, 65000).'...';\n" .
    "            }",
    $content
);

$content = str_replace(
    "'subject' => substr(\$subject, 0, 255),",
    "'subject' => strlen(\$subject) > 65000 ? substr(\$subject, 0, 65000) : \$subject,",
    $content
);

file_put_contents($file, $content);
echo "Patched EmailDispatcher.php\n";
