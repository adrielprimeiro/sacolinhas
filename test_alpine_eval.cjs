const fs = require('fs');

const html = fs.readFileSync('rendered.html', 'utf8');

// Find the x-data attribute of the user search div
// Let's find the one that has users: [
const match = html.match(/x-data="(\{\s*open:[\s\S]*?\})"/);
if (!match) {
    console.log("Could not find x-data attribute containing users!");
    process.exit(1);
}

const xDataRaw = match[1];
console.log("Found x-data. Length:", xDataRaw.length);

// In the browser, HTML entities inside attributes are decoded first.
// Let's simulate browser decoding of HTML entities:
const decodeHtmlEntities = (str) => {
    return str
        .replace(/&quot;/g, '"')
        .replace(/&apos;/g, "'")
        .replace(/&#039;/g, "'")
        .replace(/&lt;/g, '<')
        .replace(/&gt;/g, '>')
        .replace(/&amp;/g, '&');
};

const decoded = decodeHtmlEntities(xDataRaw);

try {
    const fn = new Function('return ' + decoded);
    const obj = fn();
    console.log("Successfully evaluated x-data!");
    console.log("open:", obj.open);
    console.log("search:", obj.search);
    console.log("selectedId:", obj.selectedId);
    console.log("users count:", obj.users.length);
    // Let's print the user 176 if it exists
    const u176 = obj.users.find(u => u.id === '176');
    if (u176) {
        console.log("User 176 name:", u176.name);
    }
} catch (err) {
    console.error("Syntax Error during evaluation:", err);
}
