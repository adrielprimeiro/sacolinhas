const fs = require('fs');

const bladeContent = fs.readFileSync('resources/views/admin/live/index.blade.php', 'utf8');
const scriptParts = bladeContent.split('<script>');
const jsContent = scriptParts[scriptParts.length - 1].split('</script>')[0];

const lines = jsContent.split('\n');
let insideFunction = 0;

for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    
    // Simplistic block tracking for functions
    if (line.match(/function.*\{/)) {
        insideFunction++;
    } else if (line.match(/\}\s*$/) || line.match(/^\s*\}/)) {
        if (insideFunction > 0) insideFunction--;
    }
    
    if (insideFunction === 0 && line.trim() !== '' && !line.trim().startsWith('//') && !line.match(/function.*\{/) && !line.match(/^\s*\}\s*$/) && !line.match(/^\s*\}\);\s*$/)) {
        console.log(`Line ${i + 434}: ${line.trim()}`); // roughly 434 offset
    }
}
