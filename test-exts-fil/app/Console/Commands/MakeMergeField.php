<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeMergeField extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:merge-field {--name= : The name of the merge field class}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a new merge field class inside App/MergeFields';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $className = $this->option('name');
        if (! $className) {
            $this->error('Please provide a name for the merge field.');

            return;
        }

        // Automatically append MergeFields if it's not included
        if (substr($className, -10) !== 'MergeFields') {
            $className .= 'MergeFields';
        }

        // Generate the method name
        $methodName = $this->generateMethodName($className);

        $folderPath = app_path('MergeFields');
        $filePath   = $folderPath . '/' . $className . '.php';

        // Check if the folder exists, if not, create it
        if (! File::exists($folderPath)) {
            File::makeDirectory($folderPath, 0755, true);
        }

        // Check if the file already exists
        if (File::exists($filePath)) {
            $this->error("The file {$className}.php already exists.");

            return;
        }

        // Generate the content for the class
        $content = $this->generateClassContent($className, $methodName);

        // Create the new file
        File::put($filePath, $content);

        // Success message
        $this->info("The file {$className}.php has been created successfully.");
    }

    /**
     * Generate the content for the merge field class.
     */
    private function generateClassContent(string $className, string $methodName): string
    {
        return "<?php

namespace App\\MergeFields;

class {$className}
{
    public function name(): string
    {
        return '{$methodName}';
    }

    public function build(): array
    {
        return [
            [
                'name' => '',
                'key'  => '{}',
            ],
        ];
    }

    public function format(array \$context): array
    {
        return [
            '{}' => '',
        ];
    }
}
";
    }

    /**
     * Generate method name from the class name (remove "MergeFields" and convert to lowercase with "-group").
     */
    private function generateMethodName(string $className): string
    {
        // Remove "MergeFields" if it's there and convert the first character to lowercase
        $methodName = str_replace('MergeFields', '', $className);
        $methodName = strtolower($methodName) . '-group';

        return $methodName;
    }
}
