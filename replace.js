const fs = require('fs');
const path = require('path');

function replaceInFile(filePath) {
    let content = fs.readFileSync(filePath, 'utf8');
    
    const dict = [
        ['Sub Leader', 'Asisten Marketing'],
        ['Sub leader', 'Asisten marketing'],
        ['sub leader', 'asisten marketing'],
        ['Sub-Leader', 'Asisten Marketing'],
        ['Leader', 'Marketing Utama'],
        ['leader', 'marketing utama'],
    ];

    let modified = false;

    dict.forEach(([from, to]) => {
        // Only replace word boundaries, not starting or ending with -, _, $, ., >, / or single/double quotes.
        // Node js supports negative lookbehind and lookahead
        const regex = new RegExp(`(?<![\\\\$\\\\>\\\\-\\\\.!\\\\/\\\\_\\\\\'\\\\"\\\\@a-zA-Z])(${from})(?![\\\\_\\\\-\\\\(.!\\\\/\\\\\\\\\\\\\'\\\\"\\\\@a-zA-Z])`, 'g');
        if (regex.test(content)) {
            content = content.replace(regex, to);
            modified = true;
        }
    });

    if (modified) {
        fs.writeFileSync(filePath, content, 'utf8');
        console.log('Modified: ' + filePath);
    }
}

function scan(dir) {
    fs.readdirSync(dir).forEach(file => {
        const full = path.join(dir, file);
        if (fs.statSync(full).isDirectory()) scan(full);
        else if (full.endsWith('.blade.php')) replaceInFile(full);
    });
}
scan('c:/Users/DELL/Desktop/Developer/rekap_nomor/resources/views');
