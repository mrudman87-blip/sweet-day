"""Готовит картинки для сайта: AVIF + WebP + JPG в нескольких ширинах (для srcset).
Запуск: python optimize_images.py  → результат в ../images/"""
import os
from PIL import Image

HERE = os.path.dirname(os.path.abspath(__file__))
SRC = os.path.join(HERE, "out")
DST = os.path.join(HERE, "..", "images")
os.makedirs(DST, exist_ok=True)

# имя на сайте: (исходник, ширины)
PICK = {
    "hero": ("hero_2.png", [480, 600, 800, 1024]),
    "cake-berry": ("cake-berry_2.png", [400, 600, 800]),
    "cake-choco": ("cake-choco_2.png", [400, 600, 800]),
    "cake-kids": ("cake-kids_2.png", [400, 600, 800]),
}

total = 0
for name, (src, widths) in PICK.items():
    im = Image.open(os.path.join(SRC, src)).convert("RGB")
    for w in widths:
        h = round(im.height * w / im.width)
        small = im.resize((w, h), Image.LANCZOS)
        for ext, opts in (("avif", dict(quality=55)), ("webp", dict(quality=78, method=6)),
                          ("jpg", dict(quality=80, optimize=True, progressive=True))):
            path = os.path.join(DST, f"{name}-{w}.{ext}")
            small.save(path, **opts)
            total += os.path.getsize(path)
            print(f"{os.path.basename(path):24s} {os.path.getsize(path) // 1024:4d} КБ")

# Превью для мессенджеров (Open Graph): 1200x630, обрезка по центру из главной картинки
im = Image.open(os.path.join(SRC, PICK["hero"][0])).convert("RGB")
w, h = im.size
crop_h = round(w * 630 / 1200)
top = (h - crop_h) // 2
im.crop((0, top, w, top + crop_h)).resize((1200, 630), Image.LANCZOS).save(
    os.path.join(DST, "og.jpg"), quality=82, optimize=True)
print("og.jpg готово")
