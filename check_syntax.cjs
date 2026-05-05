const fs = require('fs');

const bladeContent = fs.readFileSync('resources/views/admin/live/index.blade.php', 'utf8');
const scriptParts = bladeContent.split('<script>');
const targetScript = scriptParts[scriptParts.length - 1];
const jsContent = targetScript.split('</script>')[0];

const vm = require('vm');
try {
    new vm.Script(jsContent, { filename: 'index.blade.php' });
    console.log("No syntax errors.");
} catch (e) {
    console.log("Syntax Error:", e.message);
    console.log(e.stack);
}
