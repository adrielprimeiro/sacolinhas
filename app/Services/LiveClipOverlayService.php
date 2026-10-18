<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class LiveClipOverlayService
{
    /**
     * Retorna o caminho do vídeo motion transparente (WebM) do carimbo condizente com o estado da peça.
     */
    public static function getMotionStampPath(?string $estado): ?string
    {
        $stampsDir = public_path('images/cuts_overlay/stamps');
        $norm = mb_strtolower(trim($estado ?? ''));

        if (str_contains($norm, 'novo') && !str_contains($norm, 'semi')) {
            $file = $stampsDir . '/stamp_motion_novo.mov';
            if (file_exists($file)) return $file;
            $file = $stampsDir . '/stamp_motion_novo.webm';
            if (file_exists($file)) return $file;
        }

        if (str_contains($norm, 'usado')) {
            $file = $stampsDir . '/stamp_motion_usado.mov';
            if (file_exists($file)) return $file;
            $file = $stampsDir . '/stamp_motion_usado.webm';
            if (file_exists($file)) return $file;
        }

        $file = $stampsDir . '/stamp_motion_seminovo.mov';
        if (file_exists($file)) return $file;

        $file = $stampsDir . '/stamp_motion_seminovo.webm';
        return file_exists($file) ? $file : null;
    }

    /**
     * Retorna o caminho do carimbo realista condizente com o estado da peça (fallback PNG).
     */
    public static function getStampPath(?string $estado): string
    {
        $stampsDir = public_path('images/cuts_overlay/stamps');
        $norm = mb_strtolower(trim($estado ?? ''));

        if (str_contains($norm, 'novo') && !str_contains($norm, 'semi')) {
            $file = $stampsDir . '/stamp_novo.png';
            if (file_exists($file)) return $file;
        }

        if (str_contains($norm, 'usado')) {
            $file = $stampsDir . '/stamp_usado.png';
            if (file_exists($file)) return $file;
        }

        return $stampsDir . '/stamp_seminovo.png';
    }

    /**
     * Mapeia e retorna o caminho do arquivo PNG de logo da marca (se existir).
     */
    public static function getBrandLogoPath(?string $marca): ?string
    {
        $norm = mb_strtolower(trim($marca ?? ''));
        if (empty($norm)) {
            return null;
        }

        $generics = ['outros', 'outro', 'biju', 'bijuteria', 'bijuterias', 'outlet', 'mania', 'mani', 'saldo', 'diversos', 'geral', 'sem marca'];
        if (in_array($norm, $generics)) {
            return null;
        }

        $map = [
            'refarm' => 'refarm',
            're-farm' => 'refarm',
            're farm' => 'refarm',
            'farm' => 'farm',
            'farm rio' => 'farm',
            'melissa' => 'melissa',
            'mini melissa' => 'mini-melissa',
            'mini-melissa' => 'mini-melissa',
            'zara' => 'zara',
            'marisa' => 'marisa',
            'hering' => 'hering',
            'shein' => 'shein',
            'puket' => 'puket',
            'colcci' => 'colcci',
            'schutz' => 'schutz',
            'arezzo' => 'arezzo',
            'animale' => 'animale',
            'lança perfume' => 'lanca-perfume',
            'lanca perfume' => 'lanca-perfume',
            'lp' => 'lanca-perfume',
            'tommy' => 'tommy',
            'tommy hilfiger' => 'tommy',
            'gap' => 'gap',
            'disney' => 'disney',
            'forever 21' => 'forever-21',
            'forever21' => 'forever-21',
        ];

        $slug = $map[$norm] ?? null;
        $brandsDir = public_path('images/cuts_overlay/brands');

        if ($slug && file_exists($brandsDir . '/' . $slug . '.png')) {
            return $brandsDir . '/' . $slug . '.png';
        }

        return null;
    }

    /**
     * Retorna o caminho da fonte em negrito principal.
     */
    public static function getFontBoldPath(): string
    {
        $primary = resource_path('fonts/Roboto-Bold.ttf');
        if (file_exists($primary)) return $primary;

        $fallbacks = [
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            'C:/Windows/Fonts/arialbd.ttf',
            'C:/Windows/Fonts/Roboto-Bold.ttf',
        ];
        foreach ($fallbacks as $f) {
            if (file_exists($f)) return $f;
        }
        return $primary;
    }

    /**
     * Retorna o caminho da fonte serifada/itálica para títulos elegantes.
     */
    public static function getFontItalicPath(): string
    {
        $primary = resource_path('fonts/Georgia-Italic.ttf');
        if (file_exists($primary)) return $primary;

        $fallbacks = [
            '/usr/share/fonts/truetype/dejavu/DejaVuSerif-Italic.ttf',
            'C:/Windows/Fonts/georgiai.ttf',
            resource_path('fonts/Roboto-Bold.ttf')
        ];
        foreach ($fallbacks as $f) {
            if (file_exists($f)) return $f;
        }
        return self::getFontBoldPath();
    }

    /**
     * Gera a máscara alfa com cantos arredondados (para o corte do vídeo no card).
     */
    public static function getRoundedMaskPath(int $width = 580, int $height = 940, int $radius = 28): string
    {
        $dir = storage_path('app/temp/masks');
        if (!file_exists($dir)) {
            @mkdir($dir, 0755, true);
        }

        $maskFile = $dir . "/mask_{$width}_{$height}_{$radius}.png";
        if (file_exists($maskFile) && filesize($maskFile) > 100) {
            return $maskFile;
        }

        if (!extension_loaded('gd')) {
            return $maskFile;
        }

        $img = imagecreatetruecolor($width, $height);
        $black = imagecolorallocate($img, 0, 0, 0);
        $white = imagecolorallocate($img, 255, 255, 255);
        imagefilledrectangle($img, 0, 0, $width, $height, $black);

        self::drawRoundedRectangle($img, 0, 0, $width, $height, $radius, $white);

        imagepng($img, $maskFile);
        imagedestroy($img);

        return $maskFile;
    }

    /**
     * Gera o fundo Canvas estilizado (estilo Reel 2027 do vídeo de referência):
     * - Fundo grafite ultra escuro com grid de micro-pontos (dot-grid)
     * - Título no topo: "ACHADINHO DO DIA / curadoria exclusiva"
     * - Moldura arredondada com borda gradiente/laranja para o vídeo
     */
    public static function generateCanvasBackground(
        int $canvasW, 
        int $canvasH, 
        int $frameW, 
        int $frameH, 
        string $outputPath,
        ?string $title1 = "ACHADINHO DO DIA",
        ?string $title2 = "curadoria exclusiva"
    ): bool {
        if (!extension_loaded('gd')) return false;

        $img = imagecreatetruecolor($canvasW, $canvasH);

        // Fundo escuro premium
        $bg = imagecolorallocate($img, 10, 12, 16);
        imagefilledrectangle($img, 0, 0, $canvasW, $canvasH, $bg);

        // Micro-pontos (Dot grid)
        $dotColor = imagecolorallocate($img, 38, 42, 54);
        $spacing = 24;
        for ($x = intval($spacing / 2); $x < $canvasW; $x += $spacing) {
            for ($y = intval($spacing / 2); $y < $canvasH; $y += $spacing) {
                imagefilledellipse($img, $x, $y, 3, 3, $dotColor);
            }
        }

        // Tipografia do Topo
        $fontBold = self::getFontBoldPath();
        $fontItalic = self::getFontItalicPath();

        $textWhite = imagecolorallocate($img, 245, 247, 250);
        $textOrange = imagecolorallocate($img, 251, 146, 60);

        if (file_exists($fontBold)) {
            // Título 1: ACHADINHO DO DIA
            $bbox1 = @imagettfbbox(20, 0, $fontBold, $title1);
            if ($bbox1) {
                $tw1 = abs($bbox1[4] - $bbox1[0]);
                $tx1 = intval(($canvasW - $tw1) / 2);
                imagettftext($img, 20, 0, $tx1, 56, $textWhite, $fontBold, $title1);
            }
        }

        if (file_exists($fontItalic)) {
            // Título 2: curadoria exclusiva
            $bbox2 = @imagettfbbox(22, 0, $fontItalic, $title2);
            if ($bbox2) {
                $tw2 = abs($bbox2[4] - $bbox2[0]);
                $tx2 = intval(($canvasW - $tw2) / 2);
                imagettftext($img, 22, 0, $tx2, 92, $textOrange, $fontItalic, $title2);
            }
        }

        // Borda Laranja da Moldura do Vídeo
        $frameX = intval(($canvasW - $frameW) / 2);
        $frameY = 145;
        $borderOrange = imagecolorallocate($img, 249, 115, 22);
        $radius = 28;
        $thickness = 4;

        for ($t = 0; $t < $thickness; $t++) {
            self::drawRoundedBorder(
                $img, 
                $frameX - $t - 1, 
                $frameY - $t - 1, 
                $frameX + $frameW + $t + 1, 
                $frameY + $frameH + $t + 1, 
                $radius + $t, 
                $borderOrange
            );
        }

        $dir = dirname($outputPath);
        if (!file_exists($dir)) @mkdir($dir, 0755, true);

        $res = imagepng($img, $outputPath);
        imagedestroy($img);
        return $res;
    }

    /**
     * Gera o Card de Informações do Produto em Glassmorphism Dark moderno:
     * - Fundo translúcido slate-900 / rgba(15,23,42,0.90) com cantos arredondados
     * - Ponto verde pulsante (live dot) + Nome da Marca em caixa alta
     * - Pílula com o Tamanho ("TAM 38")
     * - Badge em verde esmeralda com o Preço ("R$ 65,00")
     */
    public static function generateGlassmorphicCard(
        $item, 
        int $cardW, 
        int $cardH, 
        string $outputPath
    ): bool {
        if (!extension_loaded('gd')) return false;

        $img = imagecreatetruecolor($cardW, $cardH);
        imagesavealpha($img, true);
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefilledrectangle($img, 0, 0, $cardW, $cardH, $transparent);

        // Fundo glassmorphism escuro
        // GD alpha varia de 0 (opaco) a 127 (transparente) -> 15% transparência = alpha ~18
        $bgGlass = imagecolorallocatealpha($img, 15, 23, 42, 16);
        $borderGlass = imagecolorallocatealpha($img, 255, 255, 255, 85);
        $radius = 18;

        self::drawRoundedRectangle($img, 2, 2, $cardW - 2, $cardH - 2, $radius, $bgGlass, $borderGlass);

        // Header: Ponto verde + Nome da Marca
        $brandName = trim($item?->marca ?? '');
        if (empty($brandName)) {
            $brandName = 'Minha Mania';
        }
        $brandNameUpper = mb_strtoupper($brandName);

        // Ponto verde esmeralda
        $dotGreen = imagecolorallocate($img, 16, 185, 129);
        imagefilledellipse($img, 24, 26, 10, 10, $dotGreen);

        $fontBold = self::getFontBoldPath();
        $textWhite = imagecolorallocate($img, 255, 255, 255);
        $textLight = imagecolorallocate($img, 226, 232, 240);
        $divider = imagecolorallocatealpha($img, 255, 255, 255, 105);

        if (file_exists($fontBold)) {
            $brandFontSize = strlen($brandNameUpper) > 16 ? 12 : 14;
            imagettftext($img, $brandFontSize, 0, 38, 31, $textWhite, $fontBold, $brandNameUpper);
        } else {
            imagestring($img, 4, 38, 18, $brandNameUpper, $textWhite);
        }

        // Linha divisória fina
        imageline($img, 16, 44, $cardW - 16, 44, $divider);

        // Conteúdo: Tamanho & Preço
        $tamanho = trim($item?->tamanho ?? '');
        $precoVal = floatval($item?->preco ?? 0);
        $precoStr = $precoVal > 0 
            ? ('R$ ' . (floor($precoVal) == $precoVal ? number_format($precoVal, 0, ',', '.') : number_format($precoVal, 2, ',', '.')))
            : '';

        $tamText = !empty($tamanho) ? ('TAM ' . mb_strtoupper($tamanho)) : 'TAM ÚNICO';

        $pillY = 56;
        $pillH = $cardH - $pillY - 14;

        if (!empty($precoStr)) {
            $sizePillW = intval($cardW * 0.36);
            $sizePillX = 16;

            // Pílula do Tamanho
            $bgPill = imagecolorallocatealpha($img, 30, 41, 59, 20);
            $borderPill = imagecolorallocatealpha($img, 255, 255, 255, 90);
            self::drawRoundedRectangle($img, $sizePillX, $pillY, $sizePillX + $sizePillW, $pillY + $pillH, 12, $bgPill, $borderPill);

            if (file_exists($fontBold)) {
                $bboxS = imagettfbbox(12, 0, $fontBold, $tamText);
                $sw = abs($bboxS[4] - $bboxS[0]);
                $sx = $sizePillX + intval(($sizePillW - $sw) / 2);
                $sy = $pillY + intval($pillH * 0.65);
                imagettftext($img, 12, 0, $sx, $sy, $textLight, $fontBold, $tamText);
            } else {
                imagestring($img, 3, $sizePillX + 10, $pillY + 10, $tamText, $textLight);
            }

            // Pílula do Preço em Destaque Verde Esmeralda
            $pricePillX = $sizePillX + $sizePillW + 10;
            $pricePillW = $cardW - $pricePillX - 16;

            $bgPrice = imagecolorallocatealpha($img, 16, 185, 129, 10);
            $borderPrice = imagecolorallocate($img, 52, 211, 153);
            self::drawRoundedRectangle($img, $pricePillX, $pillY, $pricePillX + $pricePillW, $pillY + $pillH, 12, $bgPrice, $borderPrice);

            if (file_exists($fontBold)) {
                $pFontSize = strlen($precoStr) > 9 ? 15 : 18;
                $bboxP = @imagettfbbox($pFontSize, 0, $fontBold, $precoStr);
                if ($bboxP) {
                    $pw = abs($bboxP[4] - $bboxP[0]);
                    $px = $pricePillX + intval(($pricePillW - $pw) / 2);
                    $py = $pillY + intval($pillH * 0.68);
                    imagettftext($img, $pFontSize, 0, $px, $py, $textWhite, $fontBold, $precoStr);
                }
            } else {
                imagestring($img, 4, $pricePillX + 14, $pillY + 10, $precoStr, $textWhite);
            }
        } else {
            // Apenas tamanho centralizado
            $fullPillX = 16;
            $fullPillW = $cardW - 32;
            $bgPill = imagecolorallocatealpha($img, 30, 41, 59, 20);
            $borderPill = imagecolorallocatealpha($img, 255, 255, 255, 90);
            self::drawRoundedRectangle($img, $fullPillX, $pillY, $fullPillX + $fullPillW, $pillY + $pillH, 12, $bgPill, $borderPill);

            if (file_exists($fontBold)) {
                $bboxS = imagettfbbox(14, 0, $fontBold, $tamText);
                $sw = abs($bboxS[4] - $bboxS[0]);
                $sx = $fullPillX + intval(($fullPillW - $sw) / 2);
                $sy = $pillY + intval($pillH * 0.65);
                imagettftext($img, 14, 0, $sx, $sy, $textLight, $fontBold, $tamText);
            }
        }

        $dir = dirname($outputPath);
        if (!file_exists($dir)) @mkdir($dir, 0755, true);

        $res = imagepng($img, $outputPath);
        imagedestroy($img);
        return $res;
    }

    /**
     * Desenha um retângulo com cantos arredondados preenchido e borda opcional.
     */
    protected static function drawRoundedRectangle($img, $x1, $y1, $x2, $y2, $radius, $fillColor, $borderColor = null)
    {
        imagefilledrectangle($img, $x1 + $radius, $y1, $x2 - $radius, $y2, $fillColor);
        imagefilledrectangle($img, $x1, $y1 + $radius, $x2, $y2 - $radius, $fillColor);

        imagefilledellipse($img, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $fillColor);
        imagefilledellipse($img, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $fillColor);
        imagefilledellipse($img, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $fillColor);
        imagefilledellipse($img, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $fillColor);

        if ($borderColor !== null) {
            self::drawRoundedBorder($img, $x1, $y1, $x2, $y2, $radius, $borderColor);
        }
    }

    /**
     * Desenha o contorno arredondado.
     */
    protected static function drawRoundedBorder($img, $x1, $y1, $x2, $y2, $radius, $borderColor)
    {
        imageline($img, $x1 + $radius, $y1, $x2 - $radius, $y1, $borderColor);
        imageline($img, $x1 + $radius, $y2, $x2 - $radius, $y2, $borderColor);
        imageline($img, $x1, $y1 + $radius, $x1, $y2 - $radius, $borderColor);
        imageline($img, $x2, $y1 + $radius, $x2, $y2 - $radius, $borderColor);

        imagearc($img, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, 180, 270, $borderColor);
        imagearc($img, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, 270, 360, $borderColor);
        imagearc($img, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, 0, 90, $borderColor);
        imagearc($img, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, 90, 180, $borderColor);
    }
}
