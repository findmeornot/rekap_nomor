<?php

$dirs = ['app', 'database', 'resources', 'routes', 'tests', 'docs'];
$extensions = ['php', 'js', 'vue', 'json', 'md'];

$replaceMap = [
    // --- Internal Code Standards --- //
    // Methods, Relationships, Variables
    'storeMainMarketing' => 'storeLeader',
    'storeAssistantMarketing' => 'storeSubLeader',
    '$mainMarketing' => '$leader',
    '$assistantMarketing' => '$subLeader',
    'mainMarketing' => 'leader',
    'assistantMarketing' => 'subLeader',
    
    // Constants
    'ROLE_MAIN_MARKETING' => 'ROLE_LEADER',
    'ROLE_ASSISTANT_MARKETING' => 'ROLE_SUB_LEADER',
    
    // Database and properties
    'main_marketing_id' => 'leader_id',
    'assistant_marketing_id' => 'sub_leader_id',
    "'main_marketing'" => "'leader'",
    '"main_marketing"' => '"leader"',
    "'assistant_marketing'" => "'sub_leader'",
    '"assistant_marketing"' => '"sub_leader"',

    // --- UI Replace mapping --- //
    // This is trickier since we want to catch plain strings. Let's do case-sensitive replacements first.
    'Main Marketing' => 'Marketing Utama',
    'Assistant Marketing' => 'Asisten Marketing',
    'main marketing' => 'marketing utama',
    'assistant marketing' => 'asisten marketing',
    // Now any loose variables like main_marketing that shouldn't be here (we replaced main_marketing_id above)
    // Wait, let's catch main_marketing without id
    "main_marketing" => "leader",
    "assistant_marketing" => "sub_leader",
];

// Special care: The UI replacements are to turn "Leader" / "Sub Leader" into "Marketing Utama".
// Wait, the rule says: "Use the following terminology only for user-facing UI: Marketing Utama (Leader), Asisten Marketing (Sub Leader)"
// And earlier: "Search the entire codebase and eliminate all remaining references to: main_marketing, assistant_marketing, ROLE_MAIN_MARKETING..."

function replaceInFile($filePath, $replaceMap) {
    if (!file_exists($filePath)) {
        return;
    }
    $original = file_get_contents($filePath);
    $content = $original;

    foreach ($replaceMap as $search => $replace) {
        $content = str_replace($search, $replace, $content);
    }
    
    // Specific check for translation or view files where 'Leader' might be incorrectly used in UI context
    if (strpos($filePath, 'resources\\views') !== false) {
        // Find things like {{ __('Leader') }} and change, or just change "Leader" -> "Marketing Utama"
        // But the user requested "Use the following terminology only for user-facing UI: Marketing Utama (Leader), Asisten Marketing (Sub Leader)"
        // And "eliminate all references to main_marketing"
        // If there's literally "Leader" in UI, we should replace it with "Marketing Utama". Let's wait on that and see.
    }

    if ($content !== $original) {
        file_put_contents($filePath, $content);
        echo "Updated: $filePath\n";
    }
}

foreach ($dirs as $dir) {
    if (!is_dir($dir)) continue;
    
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $fileInfo) {
        if ($fileInfo->isFile()) {
            $ext = $fileInfo->getExtension();
            // In case of blade.php, getExtension() returns php
            if (in_array($ext, $extensions)) {
                replaceInFile($fileInfo->getPathname(), $replaceMap);
            }
        }
    }
}
echo "Done.\n";
