<?php

$dirs = ['app', 'database', 'resources', 'routes', 'tests', 'docs'];
$extensions = ['php', 'md'];

$exactReplacements = [
    // Constants
    'ROLE_MAIN_MARKETING' => 'ROLE_LEADER',
    'ROLE_ASSISTANT_MARKETING' => 'ROLE_SUB_LEADER',
    'TARGET_MAIN_MARKETING' => 'TARGET_LEADER',
    'TARGET_ASSISTANT_MARKETING' => 'TARGET_SUB_LEADER',

    // Variables & Methods & Relations
    'storeMainMarketing' => 'storeLeader',
    'storeAssistantMarketing' => 'storeSubLeader',
    'isMainMarketing' => 'isLeader',
    'isAssistantMarketing' => 'isSubLeader',
    '$mainMarketing' => '$leader',
    '$assistantMarketing' => '$subLeader',
    '->mainMarketing' => '->leader',
    '->assistantMarketing' => '->subLeader',
    'mainMarketing' => 'leader',
    'assistantMarketing' => 'subLeader',
    'MainMarketing' => 'Leader',
    'AssistantMarketing' => 'SubLeader',

    // Common sentences mapped straight to UI names matching requested rules
    'Marketing Utama (Leader)' => 'Marketing Utama',
    'Asisten Marketing (Sub Leader)' => 'Asisten Marketing',
    'Main Marketing' => 'Marketing Utama',
    'Assistant Marketing' => 'Asisten Marketing',
    'main marketing' => 'marketing utama',
    'assistant marketing' => 'asisten marketing',
];

function processFile($filePath, $exactReplacements) {
    if (!file_exists($filePath)) return;
    
    $original = file_get_contents($filePath);
    $content = $original;

    // 1. Replace exact keys
    $content = strtr($content, $exactReplacements);

    // 2. Replace remaining main_marketing and assistant_marketing strings as backend concepts
    $content = str_replace('main_marketing', 'leader', $content);
    $content = str_replace('assistant_marketing', 'sub_leader', $content);

    // 3. UI concepts formatting
    // "Leader" -> "Marketing Utama" and "Sub Leader" -> "Asisten Marketing"
    // Use regex to replace these words ONLY when they are part of a sentence or label, not when they are code.
    // In views and controllers (messages):
    
    // Convert capitalized "Leader" / "Sub Leader" in text
    // We want to replace "Leader" with "Marketing Utama", but NOT in class names (like \App\Models\Leader)
    // NOT in variables (like $Leader logic if any). 
    // Mostly we find them in blade {{ __('Leader') }} or >Leader< or "Leader" (flash messages).
    
    if (preg_match('/(blade\.php|Controller\.php|Test\.php)$/', $filePath)) {
        // Flash messages or text strings
        $content = preg_replace('/(?<=[>\s\'"])Sub Leader(?=[<\s\'",:.]|$)/', 'Asisten Marketing', $content);
        $content = preg_replace('/(?<=[>\s\'"])Leader(?=[<\s\'",:.]|$)/', 'Marketing Utama', $content);
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
            if (in_array($fileInfo->getExtension(), ['php', 'md'])) { // blade.php has extension php
                processFile($fileInfo->getPathname(), $exactReplacements);
            }
        }
    }
}
echo "Done.\n";
