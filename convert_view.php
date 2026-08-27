<?php
$file = 'resources/views/admin/items/alocacao.blade.php';
$content = file_get_contents($file);

// Titles
$content = str_replace('<title>Inventário Scanner</title>', '<title>Endereçamento (Alocação)</title>', $content);
$content = str_replace('<h1>Inventário <span>Scanner</span></h1>', '<h1>Endereçamento <span>(Alocação)</span></h1>', $content);
$content = str_replace("route('inventario')", "route('items.index')", $content);

// Form Action
$content = str_replace("route('inventario.processar')", "route('alocacao.processar')", $content);

// Config fields removal
$content = preg_replace('/<div class="field">\s*<label>Novo Status.*?<\/div>/is', '', $content);
$content = preg_replace('/<div class="field">\s*<label>Cor.*?<\/div>/is', '', $content);

// Fix Localizacao field to be required and prominent
$localizacao_field = <<<HTML
            <div class="field">
                <label>Localização de Destino <span style="color:var(--danger); font-weight:700;">*</span></label>
                <input type="text" id="cfg-local" value="" placeholder="Ex: Prateleira A3, Caixa 07..." autocomplete="off">
            </div>
HTML;
$content = preg_replace('/<div class="field">\s*<label>Localização.*?<\/div>/is', $localizacao_field, $content);

// Update JS validations if any
$content = str_replace("const status = document.getElementById('cfg-status').value;", "", $content);
$content = str_replace("const cor = document.getElementById('cfg-cor').value;", "", $content);
$content = str_replace("document.getElementById('form-status').value = status;", "", $content);
$content = str_replace("document.getElementById('form-cor').value = cor;", "", $content);

// In JS, check if local is empty before opening scanner or processing
$validation_js = <<<JS
        const local = document.getElementById('cfg-local').value.trim();
        if (!local) {
            alert('A localização de destino é obrigatória!');
            return;
        }
JS;
$content = preg_replace('/btnOpen\.addEventListener\(\'click\', \(\) => \{/', "btnOpen.addEventListener('click', () => {\n" . $validation_js, $content);

// In process list
$content = str_replace("body.append('status', status);", "", $content);
$content = str_replace("body.append('cor', cor);", "", $content);

// Remove the inputs from hidden form
$content = preg_replace('/<input type="hidden" name="status".*?>/i', '', $content);
$content = preg_replace('/<input type="hidden" name="cor".*?>/i', '', $content);

file_put_contents($file, $content);
echo "View alocacao.blade.php updated successfully.\n";
