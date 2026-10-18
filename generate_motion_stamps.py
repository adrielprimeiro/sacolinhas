import os
import sys
import math
import random
import subprocess
import time
from PIL import Image, ImageDraw, ImageFont, ImageFilter

def create_grunge_mask(w, h, density=0.20, seed=42):
    random.seed(seed)
    mask = Image.new('L', (w, h), 255)
    draw = ImageDraw.Draw(mask)
    
    num_specks = int(w * h * density * 0.05)
    for _ in range(num_specks):
        x = random.randint(0, w - 1)
        y = random.randint(0, h - 1)
        r = random.choice([1, 1, 1, 2, 2, 3])
        draw.ellipse([x - r, y - r, x + r, y + r], fill=random.randint(0, 160))
        
    for _ in range(int(w * 0.1)):
        x1 = random.randint(0, w - 1)
        y1 = random.randint(0, h - 1)
        length = random.randint(4, 22)
        angle = random.uniform(0, 2 * math.pi)
        x2 = int(x1 + length * math.cos(angle))
        y2 = int(y1 + length * math.sin(angle))
        draw.line([x1, y1, x2, y2], fill=random.randint(30, 180), width=random.choice([1, 2]))
        
    return mask.filter(ImageFilter.GaussianBlur(radius=0.5))

def draw_star(draw, cx, cy, size, fill):
    points = []
    for i in range(10):
        r = size if i % 2 == 0 else size / 2.2
        angle = i * math.pi / 5 - math.pi / 2
        x = cx + r * math.cos(angle)
        y = cy + r * math.sin(angle)
        points.append((x, y))
    draw.polygon(points, fill=fill)

def get_font(size):
    candidates = [
        "resources/fonts/Impact.ttf",
        "/var/www/sacolinhas/resources/fonts/Impact.ttf",
        "/var/www/resources/fonts/Impact.ttf",
        "C:/Windows/Fonts/impact.ttf",
        "resources/fonts/Roboto-Bold.ttf",
        "/var/www/sacolinhas/resources/fonts/Roboto-Bold.ttf",
        "/var/www/resources/fonts/Roboto-Bold.ttf",
        "C:/Windows/Fonts/arialbd.ttf",
        "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf"
    ]
    for c in candidates:
        if os.path.exists(c):
            try:
                return ImageFont.truetype(c, size)
            except Exception:
                pass
    return ImageFont.load_default()

def render_curved_text_top(img, text, cx, cy, radius, font, color):
    dummy = ImageDraw.Draw(img)
    char_widths = [dummy.textbbox((0, 0), ch, font=font)[2] - dummy.textbbox((0, 0), ch, font=font)[0] + 4 for ch in text]
    total_angle = sum(char_widths) / radius
    current_angle = -math.pi / 2 - total_angle / 2
    
    for i, ch in enumerate(text):
        cw = char_widths[i]
        mid_angle = current_angle + (cw / 2) / radius
        current_angle += cw / radius
        
        x = cx + radius * math.cos(mid_angle)
        y = cy + radius * math.sin(mid_angle)
        
        bbox = dummy.textbbox((0, 0), ch, font=font)
        bw, bh = max(1, bbox[2] - bbox[0]), max(1, bbox[3] - bbox[1])
        ch_img = Image.new('RGBA', (bw + 24, bh + 24), (0, 0, 0, 0))
        ch_draw = ImageDraw.Draw(ch_img)
        ch_draw.text((12 - bbox[0], 12 - bbox[1]), ch, font=font, fill=color)
        
        deg = math.degrees(mid_angle) + 90
        rotated_ch = ch_img.rotate(-deg, resample=Image.BICUBIC, expand=True)
        img.paste(rotated_ch, (int(x - rotated_ch.width / 2), int(y - rotated_ch.height / 2)), rotated_ch)

def render_curved_text_bottom(img, text, cx, cy, radius, font, color):
    dummy = ImageDraw.Draw(img)
    char_widths = [dummy.textbbox((0, 0), ch, font=font)[2] - dummy.textbbox((0, 0), ch, font=font)[0] + 4 for ch in text]
    total_angle = sum(char_widths) / radius
    current_angle = math.pi / 2 + total_angle / 2
    
    for i, ch in enumerate(text):
        cw = char_widths[i]
        mid_angle = current_angle - (cw / 2) / radius
        current_angle -= cw / radius
        
        x = cx + radius * math.cos(mid_angle)
        y = cy + radius * math.sin(mid_angle)
        
        bbox = dummy.textbbox((0, 0), ch, font=font)
        bw, bh = max(1, bbox[2] - bbox[0]), max(1, bbox[3] - bbox[1])
        ch_img = Image.new('RGBA', (bw + 24, bh + 24), (0, 0, 0, 0))
        ch_draw = ImageDraw.Draw(ch_img)
        ch_draw.text((12 - bbox[0], 12 - bbox[1]), ch, font=font, fill=color)
        
        deg = math.degrees(mid_angle) - 90
        rotated_ch = ch_img.rotate(-deg, resample=Image.BICUBIC, expand=True)
        img.paste(rotated_ch, (int(x - rotated_ch.width / 2), int(y - rotated_ch.height / 2)), rotated_ch)

def generate_circle_component(size=600, stamp_color=(203, 32, 39)):
    img = Image.new('RGBA', (size, size), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)
    cx, cy = size // 2, size // 2
    
    r_outer = 232
    r_inner = 218
    r_core = 148
    
    # White backing for high contrast on any video
    draw.ellipse([cx - r_outer, cy - r_outer, cx + r_outer, cy + r_outer], fill=(255, 255, 255, 250))
    
    # Outer distressed circle
    draw.ellipse([cx - r_outer, cy - r_outer, cx + r_outer, cy + r_outer], outline=stamp_color + (255,), width=10)
    
    # Inner distressed circle
    draw.ellipse([cx - r_inner, cy - r_inner, cx + r_inner, cy + r_inner], outline=stamp_color + (255,), width=3)
    
    # Core red circle
    draw.ellipse([cx - r_core, cy - r_core, cx + r_core, cy + r_core], fill=stamp_color + (255,), outline=stamp_color + (255,), width=2)
    
    # 3 white stars in top area of core
    star_radius = 104
    star_angles = [-math.pi / 2 - 0.28, -math.pi / 2, -math.pi / 2 + 0.28]
    star_sizes = [14, 18, 14]
    for sa, ss in zip(star_angles, star_sizes):
        sx = cx + star_radius * math.cos(sa)
        sy = cy + star_radius * math.sin(sa)
        draw_star(draw, sx, sy, ss, (255, 255, 255, 255))
        
    font_curved = get_font(42)
    text_r = (r_inner + r_core) / 2 + 3
    render_curved_text_top(img, "100% ORIGINAL", cx, cy, text_r, font_curved, stamp_color + (255,))
    render_curved_text_bottom(img, "100% ORIGINAL", cx, cy, text_r, font_curved, stamp_color + (255,))
    
    grunge = create_grunge_mask(size, size, density=0.20, seed=101)
    r_c, g_c, b_c, a_c = img.split()
    a_distressed = Image.composite(a_c, Image.new('L', (size, size), 0), grunge)
    return Image.merge('RGBA', (r_c, g_c, b_c, a_distressed))

def generate_banner_component(estado_text="SEMINOVO", size=600, stamp_color=(203, 32, 39)):
    img = Image.new('RGBA', (size, size), (0, 0, 0, 0))
    cx, cy = size // 2, size // 2
    
    bw, bh = 490, 126
    b_img = Image.new('RGBA', (bw + 40, bh + 40), (0, 0, 0, 0))
    b_draw = ImageDraw.Draw(b_img)
    
    bx = 20
    by = 20
    corner_r = 28
    white_pad = 7
    
    # White knockout border
    b_draw.rounded_rectangle([bx - white_pad, by - white_pad, bx + bw + white_pad, by + bh + white_pad],
                             radius=corner_r + white_pad, fill=(255, 255, 255, 255))
    
    # Red background
    b_draw.rounded_rectangle([bx, by, bx + bw, by + bh], radius=corner_r, fill=stamp_color + (255,))
    
    clean_text = estado_text.strip().upper()
    font_size = 76 if len(clean_text) <= 8 else (66 if len(clean_text) <= 10 else 52)
    font_banner = get_font(font_size)
    
    bbox = b_draw.textbbox((0, 0), clean_text, font=font_banner)
    tw = bbox[2] - bbox[0]
    th = bbox[3] - bbox[1]
    tx = bx + (bw - tw) // 2
    ty = by + (bh - th) // 2 - 8
    b_draw.text((tx, ty), clean_text, font=font_banner, fill=(255, 255, 255, 255))
    
    grunge = create_grunge_mask(bw + 40, bh + 40, density=0.20, seed=202)
    r_b, g_b, b_b, a_b = b_img.split()
    a_b_distressed = Image.composite(a_b, Image.new('L', (bw + 40, bh + 40), 0), grunge)
    b_distressed = Image.merge('RGBA', (r_b, g_b, b_b, a_b_distressed))
    
    # Transversal tilt at -14 degrees
    rotated_banner = b_distressed.rotate(-14, resample=Image.BICUBIC, expand=True)
    px = cx - rotated_banner.width // 2
    py = cy - rotated_banner.height // 2
    img.paste(rotated_banner, (px, py), rotated_banner)
    return img

def ease_out_back(x, c1=1.70158):
    c3 = c1 + 1
    return 1 + c3 * math.pow(x - 1, 3) + c1 * math.pow(x - 1, 2)

def scale_and_center(source_img, scale, target_size):
    if scale <= 0.001:
        return Image.new('RGBA', (target_size, target_size), (0, 0, 0, 0))
    sw = max(1, int(round(target_size * scale)))
    sh = max(1, int(round(target_size * scale)))
    scaled = source_img.resize((sw, sh), resample=Image.BICUBIC)
    
    out = Image.new('RGBA', (target_size, target_size), (0, 0, 0, 0))
    px = (target_size - sw) // 2
    py = (target_size - sh) // 2
    out.paste(scaled, (px, py), scaled)
    return out

def build_motion_frames(estado_text="SEMINOVO", target_size=360, fps=30, total_duration_sec=60):
    base_circle = generate_circle_component(600).resize((target_size, target_size), resample=Image.LANCZOS)
    base_banner = generate_banner_component(estado_text, 600).resize((target_size, target_size), resample=Image.LANCZOS)
    
    combined = base_circle.copy()
    combined.paste(base_banner, (0, 0), base_banner)
    
    frames = []
    
    circle_duration_frames = int(0.40 * fps)   # 12 frames
    banner_duration_frames = int(0.40 * fps)   # 12 frames
    
    # 1. Circle pops from point
    for f in range(circle_duration_frames):
        p = (f + 1) / circle_duration_frames
        scale = max(0.0, ease_out_back(p, c1=1.6))
        frames.append(scale_and_center(base_circle, scale, target_size))
        
    # 2. Transversal Banner stamps over the circle
    for f in range(banner_duration_frames):
        p = (f + 1) / banner_duration_frames
        scale = max(0.0, ease_out_back(p, c1=1.8))
        scaled_banner = scale_and_center(base_banner, scale, target_size)
        
        frame = base_circle.copy()
        frame.paste(scaled_banner, (0, 0), scaled_banner)
        frames.append(frame)
        
    # 3. Sway loop (3.0s = 90 frames)
    sway_period_frames = int(3.0 * fps)
    sway_amplitude_deg = 4.0
    
    sway_cache = []
    for f in range(sway_period_frames):
        angle = sway_amplitude_deg * math.sin(2 * math.pi * f / sway_period_frames)
        rot = combined.rotate(angle, resample=Image.BICUBIC)
        sway_cache.append(rot)
        
    total_frames = int(total_duration_sec * fps)
    remaining_frames = total_frames - len(frames)
    
    for i in range(remaining_frames):
        frames.append(sway_cache[i % sway_period_frames])
        
    return frames, combined

def export_motion_video(frames, output_path, fps=30, target_size=360):
    """
    Pipes RGBA frames to FFmpeg to encode transparent alpha MOV video using png codec.
    Guarantees 100% true native RGBA transparency with zero black boxes.
    """
    cmd = [
        'ffmpeg', '-y',
        '-f', 'rawvideo',
        '-vcodec', 'rawvideo',
        '-s', f'{target_size}x{target_size}',
        '-pix_fmt', 'rgba',
        '-r', str(fps),
        '-i', '-',
        '-c:v', 'png',
        '-pix_fmt', 'rgba',
        output_path
    ]
        
    proc = subprocess.Popen(cmd, stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    for frame in frames:
        proc.stdin.write(frame.tobytes())
    stdout, stderr = proc.communicate()
    
    if proc.returncode != 0:
        print("FFmpeg Error:", stderr.decode(errors='replace'))
        return False
    return True

def generate_all_stamps(output_dir, duration_sec=30, fps=30, target_size=360):
    os.makedirs(output_dir, exist_ok=True)
    stamps = [
        ('seminovo', 'SEMINOVO', (203, 32, 39)),
        ('novo', 'NOVO', (18, 148, 75)),
        ('usado', 'USADO', (20, 100, 200))
    ]
    
    for slug, text, color in stamps:
        print(f"--- Generating Stamp [{slug}]: {text} ---")
        t0 = time.time()
        frames, static_stamp = build_motion_frames(text, target_size=target_size, fps=fps, total_duration_sec=duration_sec)
        
        # Save static PNG as fallback
        png_path = os.path.join(output_dir, f"stamp_{slug}.png")
        static_stamp.save(png_path)
        print(f"  -> Saved static PNG: {png_path}")
        
        # Save transparent MOV (png rgba)
        mov_path = os.path.join(output_dir, f"stamp_motion_{slug}.mov")
        ok_mov = export_motion_video(frames, mov_path, fps=fps, target_size=target_size)
        if ok_mov:
            print(f"  -> Saved RGBA MOV ({os.path.getsize(mov_path)/1024:.1f} KB): {mov_path}")
            
        print(f"Done [{slug}] in {time.time() - t0:.2f}s")

if __name__ == "__main__":
    out_dir = sys.argv[1] if len(sys.argv) > 1 else "public/images/cuts_overlay/stamps"
    dur = int(sys.argv[2]) if len(sys.argv) > 2 else 60
    generate_all_stamps(out_dir, duration_sec=dur)
