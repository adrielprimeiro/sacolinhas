const fs = require('fs');

const bladeContent = fs.readFileSync('resources/views/admin/live/index.blade.php', 'utf8');
const scriptParts = bladeContent.split('<script>');
const targetScript = scriptParts[scriptParts.length - 1];
const jsContent = targetScript.split('</script>')[0];

let indent = 0;
const lines = jsContent.split('\n');

for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    const openBraces = (line.match(/\{/g) || []).length;
    const closeBraces = (line.match(/\}/g) || []).length;
    
    // Ignore braces inside strings (naive check)
    // Actually, let's just log the line if indent goes below 0 or at the end
    indent += openBraces - closeBraces;
}
console.log("Final indent:", indent);
