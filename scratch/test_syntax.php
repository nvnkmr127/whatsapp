<?php
require 'vendor/autoload.php';
try {
    class TestTrait {
        use \App\Traits\WhatsApp;
        protected \$team;
    }
    echo "Syntax OK\n";
} catch (\Throwable \$e) {
    echo "Error: " . \$e->getMessage() . " at " . \$e->getFile() . ":" . \$e->getLine() . "\n";
}
