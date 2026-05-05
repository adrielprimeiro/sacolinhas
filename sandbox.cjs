const fs = require('fs');

const bladeContent = fs.readFileSync('resources/views/admin/live/index.blade.php', 'utf8');
const scriptParts = bladeContent.split('<script>');
const targetScript = scriptParts[scriptParts.length - 1];
const jsContent = targetScript.substring(0, targetScript.indexOf('</script>'));

// Mocks
const originalLog = console.log;
global.document = {
    addEventListener: () => {},
    querySelector: (sel) => {
        if (sel === 'meta[name="csrf-token"]') return { getAttribute: () => 'token' };
        return { 
            addEventListener: () => {}, 
            value: '', 
            classList: { add: () => {}, remove: () => {} },
            style: {},
            querySelector: () => ({ addEventListener: () => {}, value: '', style: {}, classList: { add: () => {}, remove: () => {} } })
        };
    },
    querySelectorAll: () => [],
    getElementById: (id) => {
        return { 
            addEventListener: () => {}, 
            value: '', 
            classList: { add: () => {}, remove: () => {} },
            style: {},
            innerHTML: ''
        };
    },
    dispatchEvent: () => {}
};
global.window = global;
global.fetch = () => Promise.resolve({ text: () => Promise.resolve('{}') });
global.FormData = class {};
global.CustomEvent = class {};
global.console = { log: () => {}, error: () => {} };

try {
    eval(jsContent);
    originalLog("SUCESSO: Script executado sem erros no sandbox!");
} catch (e) {
    originalLog("ERRO DE RUNTIME ENCONTRADO:", e.name, e.message);
    originalLog(e.stack);
}
