import sys
import os
import io
from PIL import Image

try:
    from rembg import remove
except ImportError:
    print("Erro: A biblioteca 'rembg' não está instalada.", file=sys.stderr)
    sys.exit(1)

def process_image(input_path, output_path):
    if not os.path.exists(input_path):
        print(f"Erro: Arquivo de entrada '{input_path}' não encontrado.", file=sys.stderr)
        sys.exit(1)

    try:
        # Lê a imagem de entrada como bytes
        with open(input_path, 'rb') as i:
            input_data = i.read()
        
        # Remove o fundo (gera imagem com transparência RGBA)
        output_data = remove(input_data)
        
        # Abre a imagem resultante via Pillow
        img = Image.open(io.BytesIO(output_data))
        
        # Garante que a imagem está em modo que suporta transparência antes de colar
        if img.mode != 'RGBA':
            img = img.convert('RGBA')

        # Cria uma prancheta de fundo branco puro
        background = Image.new("RGB", img.size, (255, 255, 255))
        
        # Sobrepõe o recorte no fundo branco usando o canal Alpha como máscara
        background.paste(img, mask=img.split()[3])
        
        # Salva o resultado final como JPEG de alta qualidade
        background.save(output_path, "JPEG", quality=95)
        
        print(f"SUCCESS: {output_path}")
        
    except Exception as e:
        print(f"ERROR: Detalhes do processamento: {str(e)}", file=sys.stderr)
        sys.exit(1)

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("Uso: python image_processor.py <caminho_entrada> <caminho_saida>", file=sys.stderr)
        sys.exit(1)
        
    process_image(sys.argv[1], sys.argv[2])
