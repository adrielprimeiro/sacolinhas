<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class LiveClipOverlayService
{
    /**
     * Retorna o caminho do carimbo condizente com o estado da peça.
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
     * Mapeia e retorna o caminho do card de logo da marca.
     */
    public static function getBrandCardPath(?string $marca): ?string
    {
        $norm = mb_strtolower(trim($marca ?? ''));
        if (empty($norm)) {
            return null;
        }

        // Ignora marcas genéricas para não poluir o vídeo
        $generics = ['outros', 'outro', 'biju', 'bijuteria', 'bijuterias', 'outlet', 'mania', 'mani', 'saldo', 'diversos', 'geral', 'sem marca'];
        if (in_array($norm, $generics)) {
            return null;
        }

        // Mapeamento de slugs
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

        // Se for outra marca com nome válido, gera um card temporário estilizado
        return self::generateDynamicBrandCard($marca);
    }

    /**
     * Gera dinamicamente um card branco elegante para marcas que ainda não possuem PNG cadastrado.
     */
    protected static function generateDynamicBrandCard(string $marca): ?string
    {
        $clean = trim($marca);
        if (strlen($clean) < 2) {
            return null;
        }

        $cacheDir = storage_path('app/temp/brand_cards');
        if (!file_exists($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }

        $hash = md5(mb_strtolower($clean));
        $cacheFile = $cacheDir . '/dyn_' . $hash . '.png';
        if (file_exists($cacheFile)) {
            return $cacheFile;
        }

        $w = 420;
        $h = 180;
        $img = imagecreatetruecolor($w, $h);
        imagesavealpha($img, true);
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefilledrectangle($img, 0, 0, $w, $h, $transparent);

        // Fundo do card
        $white = imagecolorallocate($img, 255, 255, 255);
        $border = imagecolorallocate($img, 215, 220, 230);
        $textDark = imagecolorallocate($img, 20, 20, 20);

        self::drawRoundedRectangle($img, 4, 4, $w - 4, $h - 4, 20, $white, $border);

        $fontPath = resource_path('fonts/Roboto-Bold.ttf');
        $displayName = mb_strtoupper($clean);
        $fontSize = strlen($displayName) > 12 ? 22 : 28;

        if (file_exists($fontPath)) {
            $bbox = imagettfbbox($fontSize, 0, $fontPath, $displayName);
            $tw = abs($bbox[4] - bbox[0]);
            $th = abs($bbox[5] - bbox[1]);
            $tx = intval(($w - $tw) / 2);
            $ty = intval(($h + $th) / 2) - 4;
            imagettftext($img, $fontSize, 0, $tx, $ty, $textDark, $fontPath, $displayName);
        } else {
            // Fallback sem TTF
            $tx = intval(($w - strlen($displayName) * 9) / 2);
            imagestring($img, 5, $tx, intval($h / 2) - 8, $displayName, $textDark);
        }

        imagepng($img, $cacheFile);
        imagedestroy($img);

        return $cacheFile;
    }

    /**
     * Gera a imagem PNG transparente do overlay com todos os elementos posicionados.
     */
    public static function generateOverlayImage($item, int $videoWidth, int $videoHeight, string $outputPngPath): bool
    {
        if (!extension_loaded('gd')) {
            Log::warning('[LiveClipOverlayService] Extensão PHP GD não disponível.');
            return false;
        }

        $canvas = imagecreatetruecolor($videoWidth, $videoHeight);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $videoWidth, $videoHeight, $transparent);

        // 1. CARIMBO DE ESTADO (Superior Direito)
        $stampFile = self::getStampPath($item?->estado);
        if (file_exists($stampFile)) {
            $stampImg = @imagecreatefrompng($stampFile);
            if ($stampImg) {
                $sw = imagesx($stampImg);
                $sh = imagesy($stampImg);
                $targetStampSize = intval($videoWidth * 0.26); // ~187px em 720p
                $stampX = $videoWidth - $targetStampSize - intval($videoWidth * 0.035);
                $stampY = intval($videoHeight * 0.065); // seguro contra barras superiores do Instagram Stories

                imagecopyresampled($canvas, $stampImg, $stampX, $stampY, 0, 0, $targetStampSize, $targetStampSize, $sw, $sh);
                imagedestroy($stampImg);
            }
        }

        // 2. CARD DE TAMANHO & PREÇO (Meio Direito)
        $tamanho = trim($item?->tamanho ?? '');
        $precoVal = floatval($item?->preco ?? 0);
        $precoStr = $precoVal > 0 ? ('R$ ' . (floor($precoVal) == $precoVal ? number_format($precoVal, 0, ',', '.') : number_format($precoVal, 2, ',', '.'))) : '';

        if (!empty($tamanho) || !empty($precoStr)) {
            $cardW = intval($videoWidth * 0.28);
            $cardH = intval($videoHeight * 0.095);
            $cardX = $videoWidth - $cardW - intval($videoWidth * 0.035);
            $cardY = intval($videoHeight * 0.38);

            $cardImg = imagecreatetruecolor($cardW, $cardH);
            imagesavealpha($cardImg, true);
            $cardTrans = imagecolorallocatealpha($cardImg, 0, 0, 0, 127);
            imagefilledrectangle($cardImg, 0, 0, $cardW, $cardH, $cardTrans);

            $white = imagecolorallocate($cardImg, 255, 255, 255);
            $border = imagecolorallocate($cardImg, 220, 225, 230);
            $textDark = imagecolorallocate($cardImg, 15, 15, 15);

            self::drawRoundedRectangle($cardImg, 2, 2, $cardW - 2, $cardH - 2, 16, $white, $border);

            $fontPath = resource_path('fonts/Roboto-Bold.ttf');
            $tamText = !empty($tamanho) ? ('Tam ' . $tamanho) : '';

            if (file_exists($fontPath)) {
                $fontSize = max(14, intval($videoWidth * 0.030));
                
                if (!empty($tamText) && !empty($precoStr)) {
                    // Duas linhas
                    $bbox1 = imagettfbbox($fontSize, 0, $fontPath, $tamText);
                    $tw1 = abs($bbox1[4] - $bbox1[0]);
                    $tx1 = intval(($cardW - $tw1) / 2);
                    $ty1 = intval($cardH * 0.44);
                    imagettftext($cardImg, $fontSize, 0, $tx1, $ty1, $textDark, $fontPath, $tamText);

                    $bbox2 = imagettfbbox($fontSize, 0, $fontPath, $precoStr);
                    $tw2 = abs($bbox2[4] - $bbox2[0]);
                    $tx2 = intval(($cardW - $tw2) / 2);
                    $ty2 = intval($cardH * 0.85);
                    imagettftext($cardImg, $fontSize, 0, $tx2, $ty2, $textDark, $fontPath, $precoStr);
                } else {
                    // Linha única centralizada
                    $singleText = $tamText ?: $precoStr;
                    $bbox = imagettfbbox($fontSize + 4, 0, $fontPath, $singleText);
                    $tw = abs($bbox[4] - $bbox[0]);
                    $th = abs($bbox[5] - $bbox[1]);
                    $tx = intval(($cardW - $tw) / 2);
                    $ty = intval(($cardH + $th) / 2);
                    imagettftext($cardImg, $fontSize + 4, 0, $tx, $ty, $textDark, $fontPath, $singleText);
                }
            } else {
                // Fallback imagestring
                if (!empty($tamText)) {
                    imagestring($cardImg, 5, intval(($cardW - strlen($tamText) * 9) / 2), intval($cardH * 0.20), $tamText, $textDark);
                }
                if (!empty($precoStr)) {
                    imagestring($cardImg, 5, intval(($cardW - strlen($precoStr) * 9) / 2), intval($cardH * 0.60), $precoStr, $textDark);
                }
            }

            imagecopy($canvas, $cardImg, $cardX, $cardY, 0, 0, $cardW, $cardH);
            imagedestroy($cardImg);
        }

        // 3. CARD DO LOGO DA MARCA (Inferior Direito)
        $brandCardFile = self::getBrandCardPath($item?->marca);
        if ($brandCardFile && file_exists($brandCardFile)) {
            $brandImg = @imagecreatefrompng($brandCardFile);
            if ($brandImg) {
                $bwOrig = imagesx($brandImg);
                $bhOrig = imagesy($brandImg);

                $targetBw = intval($videoWidth * 0.29); // ~210px em 720p
                $targetBh = intval($targetBw * ($bhOrig / $bwOrig));
                $brandX = $videoWidth - $targetBw - intval($videoWidth * 0.035);
                $brandY = intval($videoHeight * 0.68); // acima da zona de rodapé dos Stories

                imagecopyresampled($canvas, $brandImg, $brandX, $brandY, 0, 0, $targetBw, $targetBh, $bwOrig, $bhOrig);
                imagedestroy($brandImg);
            }
        }

        // Salva imagem final
        $dir = dirname($outputPngPath);
        if (!file_exists($dir)) {
            @mkdir($dir, 0755, true);
        }

        $res = imagepng($canvas, $outputPngPath);
        imagedestroy($canvas);

        return $res;
    }

    /**
     * Desenha um retângulo com cantos arredondados e borda.
     */
    protected static function drawRoundedRectangle($img, $x1, $y1, $x2, $y2, $radius, $fillColor, $borderColor = null)
    {
        // Retângulo central
        imagefilledrectangle($img, $x1 + $radius, $y1, $x2 - $radius, $y2, $fillColor);
        imagefilledrectangle($img, $x1, $y1 + $radius, $x2, $y2 - $radius, $fillColor);

        // Cantos
        imagefilledellipse($img, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $fillColor);
        imagefilledellipse($img, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $fillColor);
        imagefilledellipse($img, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $fillColor);
        imagefilledellipse($img, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $fillColor);

        if ($borderColor !== null) {
            // Bordas horizontais e verticais
            imageline($img, $x1 + $radius, $y1, $x2 - $radius, $y1, $borderColor);
            imageline($img, $x1 + $radius, $y2, $x2 - $radius, $y2, $borderColor);
            imageline($img, $x1, $y1 + $radius, $x1, $y2 - $radius, $borderColor);
            imageline($img, $x2, $y1 + $radius, $x2, $y2 - $radius, $borderColor);

            // Arcos dos cantos
            imagearc($img, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, 180, 270, $borderColor);
            imagearc($img, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, 270, 360, $borderColor);
            imagearc($img, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, 0, 90, $borderColor);
            imagearc($img, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, 90, 180, $borderColor);
        }
    }
}
