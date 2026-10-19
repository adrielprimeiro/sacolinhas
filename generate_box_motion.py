import os
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

def generate_transparent_stamp(estado_text="SEMINOVO", size=600, stamp_color=(198, 25, 32)):
    """
    Generates the authentic rubber stamp with transparent background
    so ink stamps directly into the kraft cardboard fibers.
    """
    img = Image.new('RGBA', (size, size), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)
    cx, cy = size // 2, size // 2
    
    r_outer = 232
    r_inner = 218
    r_core = 148
    
    # Outer circle
    draw.ellipse([cx - r_outer, cy - r_outer, cx + r_outer, cy + r_outer], outline=stamp_color + (255,), width=11)
    # Inner circle
    draw.ellipse([cx - r_inner, cy - r_inner, cx + r_inner, cy + r_inner], outline=stamp_color + (255,), width=4)
    # Core circle
    draw.ellipse([cx - r_core, cy - r_core, cx + r_core, cy + r_core], fill=stamp_color + (255,), outline=stamp_color + (255,), width=2)
    
    # 3 white stars
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
    
    grunge = create_grunge_mask(size, size, density=0.22, seed=101)
    r_c, g_c, b_c, a_c = img.split()
    a_distressed = Image.composite(a_c, Image.new('L', (size, size), 0), grunge)
    circle_img = Image.merge('RGBA', (r_c, g_c, b_c, a_distressed))
    
    # Transversal banner
    bw, bh = 490, 126
    b_img = Image.new('RGBA', (bw + 40, bh + 40), (0, 0, 0, 0))
    b_draw = ImageDraw.Draw(b_img)
    bx, by = 20, 20
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
    
    grunge_b = create_grunge_mask(bw + 40, bh + 40, density=0.20, seed=202)
    r_b, g_b, b_b, a_b = b_img.split()
    a_b_distressed = Image.composite(a_b, Image.new('L', (bw + 40, bh + 40), 0), grunge_b)
    b_distressed = Image.merge('RGBA', (r_b, g_b, b_b, a_b_distressed))
    
    rotated_banner = b_distressed.rotate(-14, resample=Image.BICUBIC, expand=True)
    px = cx - rotated_banner.width // 2
    py = cy - rotated_banner.height // 2
    
    circle_img.paste(rotated_banner, (px, py), rotated_banner)
    return circle_img

def create_cardboard_texture(w, h, base_color=(205, 164, 115), seed=42):
    random.seed(seed)
    img = Image.new('RGB', (w, h), base_color)
    draw = ImageDraw.Draw(img)
    
    for y in range(0, h, 3):
        shade = random.randint(-7, 7)
        c = tuple(max(0, min(255, ch + shade)) for ch in base_color)
        draw.line([0, y, w, y], fill=c, width=1)
        
    num_fibers = int(w * h * 0.0035)
    for _ in range(num_fibers):
        x = random.randint(0, w - 1)
        y = random.randint(0, h - 1)
        length = random.randint(2, 6)
        angle = random.uniform(-0.25, 0.25)
        x2 = int(x + length * math.cos(angle))
        y2 = int(y + length * math.sin(angle))
        shade = random.choice([-20, -14, 12, 18])
        c = tuple(max(0, min(255, ch + shade)) for ch in base_color)
        draw.line([x, y, x2, y2], fill=c, width=1)
        
    return img.filter(ImageFilter.GaussianBlur(radius=0.5))

def render_kraft_box(w=380, h=380, box_w=240, box_h=230, box_depth=42):
    """
    Renders the clean kraft cardboard delivery package.
    """
    img = Image.new('RGBA', (w, h), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)
    cx, cy = w // 2 - 8, h // 2 + 10
    
    fx1 = cx - box_w // 2
    fx2 = cx + box_w // 2
    fy1 = cy - box_h // 2 + 18
    fy2 = cy + box_h // 2 + 18
    
    dx = box_depth
    dy = int(box_depth * 0.46)
    
    # 1. Soft ambient shadow under box
    shadow_w = box_w + dx + 24
    shadow_img = Image.new('RGBA', (w, h), (0, 0, 0, 0))
    s_draw = ImageDraw.Draw(shadow_img)
    s_draw.ellipse([cx - shadow_w//2 + 12, fy2 - 12, cx + shadow_w//2 + 12, fy2 + 36], fill=(0, 0, 0, 85))
    shadow_img = shadow_img.filter(ImageFilter.GaussianBlur(radius=10))
    img.paste(shadow_img, (0, 0), shadow_img)
    
    # Textures
    tex_front = create_cardboard_texture(box_w + 10, box_h + 10, base_color=(205, 164, 115), seed=1)
    
    # 2. Right Side Face
    right_pts = [(fx2, fy1), (fx2 + dx, fy1 - dy), (fx2 + dx, fy2 - dy), (fx2, fy2)]
    draw.polygon(right_pts, fill=(168, 126, 82, 255))
    draw.line([(fx2, fy1), (fx2, fy2)], fill=(138, 98, 58, 220), width=2)
    draw.line([(fx2 + dx, fy1 - dy), (fx2 + dx, fy2 - dy)], fill=(125, 88, 48, 200), width=2)
    draw.line([(fx2, fy2), (fx2 + dx, fy2 - dy)], fill=(120, 80, 45, 220), width=2)
    
    # 3. Top Face
    top_pts = [(fx1, fy1), (fx1 + dx, fy1 - dy), (fx2 + dx, fy1 - dy), (fx2, fy1)]
    draw.polygon(top_pts, fill=(226, 188, 140, 255))
    draw.line([(fx1, fy1), (fx1 + dx, fy1 - dy)], fill=(188, 148, 102, 220), width=2)
    draw.line([(fx1 + dx, fy1 - dy), (fx2 + dx, fy1 - dy)], fill=(188, 148, 102, 220), width=2)
    
    # 4. Translucent Packing Tape on Top
    tape_w = 26
    t_mid1_x = (fx1 + fx2) // 2
    t_mid2_x = t_mid1_x + dx
    tape_pts = [
        (t_mid1_x - tape_w//2, fy1),
        (t_mid2_x - tape_w//2, fy1 - dy),
        (t_mid2_x + tape_w//2, fy1 - dy),
        (t_mid1_x + tape_w//2, fy1)
    ]
    draw.polygon(tape_pts, fill=(245, 235, 215, 180))
    draw.line([(t_mid1_x - tape_w//2, fy1), (t_mid2_x - tape_w//2, fy1 - dy)], fill=(210, 195, 170, 200), width=1)
    draw.line([(t_mid1_x + tape_w//2, fy1), (t_mid2_x + tape_w//2, fy1 - dy)], fill=(210, 195, 170, 200), width=1)
    
    # 5. Front Face (solid kraft texture)
    front_mask = Image.new('L', (w, h), 0)
    fm_draw = ImageDraw.Draw(front_mask)
    corner_r = 3
    fm_draw.rounded_rectangle([fx1, fy1, fx2, fy2], radius=corner_r, fill=255)
    
    tex_full = Image.new('RGBA', (w, h), (0, 0, 0, 0))
    tex_full.paste(tex_front, (fx1, fy1))
    img.paste(tex_full, (0, 0), front_mask)
    
    # Front outlines
    draw.rounded_rectangle([fx1, fy1, fx2, fy2], radius=corner_r, outline=(158, 118, 76, 255), width=2)
    draw.line([fx1 + 2, fy1 + 1, fx2 - 2, fy1 + 1], fill=(238, 204, 158, 210), width=1)
    
    # Front Tape Tab
    draw.rectangle([t_mid1_x - tape_w//2, fy1, t_mid1_x + tape_w//2, fy1 + 26], fill=(245, 235, 215, 195))
    draw.line([t_mid1_x - tape_w//2, fy1, t_mid1_x - tape_w//2, fy1 + 26], fill=(210, 195, 170, 220), width=1)
    draw.line([t_mid1_x + tape_w//2, fy1, t_mid1_x + tape_w//2, fy1 + 26], fill=(210, 195, 170, 220), width=1)
    draw.line([t_mid1_x - tape_w//2, fy1 + 26, t_mid1_x + tape_w//2, fy1 + 26], fill=(190, 170, 140, 220), width=1)
    
    # 6. Charming Shipping Label / Barcode on Lower Left
    lx1 = fx1 + 14
    ly1 = fy2 - 42
    lx2 = lx1 + 52
    ly2 = fy2 - 12
    draw.rounded_rectangle([lx1, ly1, lx2, ly2], radius=3, fill=(250, 248, 244, 230), outline=(210, 200, 185, 200), width=1)
    # Barcode lines
    random.seed(99)
    bx_cur = lx1 + 6
    while bx_cur < lx2 - 6:
        bar_w = random.choice([1, 1, 2])
        draw.line([bx_cur, ly1 + 6, bx_cur, ly2 - 8], fill=(60, 50, 40, 230), width=bar_w)
        bx_cur += bar_w + random.choice([2, 3])
    # Tiny postal stamp code "MM"
    font_tiny = get_font(8)
    draw.text((lx1 + 8, ly2 - 7), "MM-EXPRESS", font=font_tiny, fill=(100, 90, 80, 220))
    
    # Stamp center
    stamp_cx = (fx1 + fx2) // 2
    stamp_cy = (fy1 + fy2) // 2 + 10
    
    return img, (stamp_cx, stamp_cy)

def apply_squash(img, scale_x, scale_y, base_y=None):
    """
    Squashes and stretches an image anchored at the bottom contact point (base_y).
    """
    w, h = img.size
    if base_y is None:
        base_y = int(h * 0.88)
        
    new_w = max(1, int(round(w * scale_x)))
    new_h = max(1, int(round(h * scale_y)))
    
    resized = img.resize((new_w, new_h), resample=Image.BICUBIC)
    out = Image.new('RGBA', (w, h), (0, 0, 0, 0))
    
    # Anchor at center X, base_y
    new_base_y = int(base_y * scale_y)
    px = (w - new_w) // 2
    py = base_y - new_base_y
    out.paste(resized, (px, py), resized)
    return out

def build_box_motion_frames(estado_text="SEMINOVO", target_size=360, fps=30, total_duration_sec=30):
    """
    Builds the complete animation according to user prompt:
    1. Shows clean box standing still (0.0s to 0.40s)
    2. Stamp slams down onto the box with squash impact (0.40s to 0.75s)
    3. Stamped box does smooth lively animations: hover float + organic pendulum sway (0.75s onwards)
    """
    # 1. Base clean box
    clean_box, (scx, scy) = render_kraft_box(target_size, target_size, box_w=225, box_h=215, box_depth=38)
    
    # 2. Transparent rubber stamp for cardboard
    stamp_raw = generate_transparent_stamp(estado_text, size=600)
    stamp_target_w = 175
    stamp_scaled = stamp_raw.resize((stamp_target_w, stamp_target_w), resample=Image.LANCZOS)
    
    # Pre-render fully stamped box
    stamped_box = clean_box.copy()
    spx = scx - stamp_target_w // 2
    spy = scy - stamp_target_w // 2
    stamped_box.paste(stamp_scaled, (spx, spy), stamp_scaled)
    
    frames = []
    
    # Phase 1: Clean box standing still (0.0s to 0.35s = 11 frames)
    # Has a cute tiny settle at the very beginning (scale 0.98 -> 1.0)
    clean_box_frames = int(0.35 * fps) # 11 frames
    for f in range(clean_box_frames):
        frames.append(clean_box.copy())
        
    # Phase 2: Stamp incoming & Impact Squash (0.35s to 0.75s = 12 frames)
    # Frame 11 (t=0.37s): stamp starts high above camera (scale 1.5, opacity 0.4)
    # Frame 13 (t=0.43s): stamp hits box!
    # Frames 14-19: box squashes vertically and rebounds elastically!
    impact_frames = int(0.40 * fps) # 12 frames
    
    for f in range(impact_frames):
        if f == 0:
            # Stamp rushing in from above
            s_scale = 1.35
            sw = int(stamp_target_w * s_scale)
            incoming = stamp_scaled.resize((sw, sw), resample=Image.BICUBIC)
            # 50% opacity
            r, g, b, a = incoming.split()
            a = a.point(lambda p: int(p * 0.45))
            incoming = Image.merge('RGBA', (r, g, b, a))
            
            f_img = clean_box.copy()
            f_img.paste(incoming, (scx - sw//2, scy - sw//2 - 25), incoming)
            frames.append(f_img)
        elif f == 1:
            # Stamp almost at contact (scale 1.15, 80% opacity)
            s_scale = 1.15
            sw = int(stamp_target_w * s_scale)
            incoming = stamp_scaled.resize((sw, sw), resample=Image.BICUBIC)
            r, g, b, a = incoming.split()
            a = a.point(lambda p: int(p * 0.80))
            incoming = Image.merge('RGBA', (r, g, b, a))
            
            f_img = clean_box.copy()
            f_img.paste(incoming, (scx - sw//2, scy - sw//2 - 10), incoming)
            frames.append(f_img)
        elif f == 2:
            # IMPACT! Stamp hits the box, maximum squash!
            # Box squashes down: scale_y = 0.88, scale_x = 1.08
            squashed = apply_squash(stamped_box, scale_x=1.08, scale_y=0.88)
            frames.append(squashed)
        elif f == 3:
            # Elastic recoil up: scale_y = 1.06, scale_x = 0.96
            squashed = apply_squash(stamped_box, scale_x=0.96, scale_y=1.06)
            frames.append(squashed)
        elif f == 4:
            # Recoil down: scale_y = 0.98, scale_x = 1.01
            squashed = apply_squash(stamped_box, scale_x=1.01, scale_y=0.98)
            frames.append(squashed)
        elif f == 5:
            # Rebound: scale_y = 1.02, scale_x = 0.99
            squashed = apply_squash(stamped_box, scale_x=0.99, scale_y=1.02)
            frames.append(squashed)
        else:
            # Settle smoothly to 1.0
            p = (f - 5) / (impact_frames - 5)
            sy = 1.02 - 0.02 * p
            sx = 0.99 + 0.01 * p
            squashed = apply_squash(stamped_box, scale_x=sx, scale_y=sy)
            frames.append(squashed)
            
    # Phase 3: Stamped box animation loop (0.75s to total_duration_sec)
    # Loop duration: 3.0s (90 frames)
    # - Smooth pendulum sway: theta = 4.0 deg * sin(2*pi*f/90)
    # - Hover bobbing: delta_y = -4px * sin(2*pi*f/90)
    # - Subtle breathing: scale_x/scale_y oscillate by 1.2%
    sway_period_frames = int(3.0 * fps) # 90 frames
    sway_cache = []
    
    for f in range(sway_period_frames):
        phase = 2 * math.pi * f / sway_period_frames
        angle = 3.8 * math.sin(phase)
        bob_y = int(-4.0 * math.sin(phase))
        
        # Subtle breathing
        breath_y = 1.0 + 0.015 * math.cos(phase)
        breath_x = 1.0 - 0.010 * math.cos(phase)
        
        breathed = apply_squash(stamped_box, breath_x, breath_y)
        rotated = breathed.rotate(angle, resample=Image.BICUBIC)
        
        # Apply hover bobbing vertical shift
        if bob_y != 0:
            bobbed = Image.new('RGBA', (target_size, target_size), (0, 0, 0, 0))
            bobbed.paste(rotated, (0, bob_y), rotated)
            sway_cache.append(bobbed)
        else:
            sway_cache.append(rotated)
            
    total_frames = int(total_duration_sec * fps)
    remaining_frames = total_frames - len(frames)
    
    for i in range(remaining_frames):
        frames.append(sway_cache[i % sway_period_frames])
        
    return frames, stamped_box

def export_motion_video(frames, output_path, fps=30, target_size=360):
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

def generate_all_box_stamps(output_dir, duration_sec=30, fps=30, target_size=360):
    os.makedirs(output_dir, exist_ok=True)
    stamps = [
        ('seminovo', 'SEMINOVO'),
        ('novo', 'NOVO'),
        ('usado', 'USADO')
    ]
    
    for slug, text in stamps:
        print(f"--- Generating Animated Box Stamp [{slug}]: {text} ---")
        t0 = time.time()
        frames, static_stamped_box = build_box_motion_frames(text, target_size=target_size, fps=fps, total_duration_sec=duration_sec)
        
        # Save static PNG fallback
        png_path = os.path.join(output_dir, f"stamp_{slug}.png")
        static_stamped_box.save(png_path)
        print(f"  -> Saved static PNG: {png_path}")
        
        # Save transparent MOV (png rgba)
        mov_path = os.path.join(output_dir, f"stamp_motion_{slug}.mov")
        ok_mov = export_motion_video(frames, mov_path, fps=fps, target_size=target_size)
        if ok_mov:
            print(f"  -> Saved RGBA MOV ({os.path.getsize(mov_path)/1024:.1f} KB): {mov_path}")
            
        print(f"Done [{slug}] in {time.time() - t0:.2f}s")

if __name__ == "__main__":
    import sys
    out_dir = sys.argv[1] if len(sys.argv) > 1 else "public/images/cuts_overlay/stamps"
    dur = int(sys.argv[2]) if len(sys.argv) > 2 else 30
    generate_all_box_stamps(out_dir, duration_sec=dur)

