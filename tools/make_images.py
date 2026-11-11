"""Генерация картинок для лендинга «Сладкий день» локальной моделью Flux (видеокарта).
Запускается двойным щелчком по make_images.bat. Результат — папка tools/out/ (по 2 варианта)."""
import os
import subprocess
import sys

PY = r"D:\Tools\image_venv\Scripts\python.exe"
GEN = os.path.join(os.path.expanduser("~"), ".claude", "skills", "image", "flux_gen.py")
OUT = os.path.join(os.path.dirname(os.path.abspath(__file__)), "out")
os.makedirs(OUT, exist_ok=True)

JOBS = [
    ("hero", "1024x1024",
     "Professional food photography of an elegant two-tier white cream cake decorated with fresh "
     "strawberries, raspberries, blueberries and small edible pink flowers, on a light ceramic cake stand, "
     "soft warm pastel peach background, natural window light from the left, shallow depth of field, "
     "airy and premium bakery style, no text"),
    ("cake-berry", "1024x768",
     "Professional food photography of a vanilla sponge cake with white whipped cream frosting topped with "
     "fresh strawberries and blueberries, slice cut showing layers, on a pastel plate, soft cream background, "
     "natural light, appetizing, bakery menu photo, no text"),
    ("cake-choco", "1024x768",
     "Professional food photography of a glossy dark chocolate ganache cake decorated with fresh cherries and "
     "chocolate curls, on a dark ceramic plate, warm beige background, soft natural light, appetizing, "
     "premium bakery menu photo, no text"),
    ("cake-kids", "1024x768",
     "Professional food photography of a colorful children's birthday cake with pastel rainbow cream frosting, "
     "sprinkles, small meringue clouds and macarons on top, on a white cake stand, light pastel pink background, "
     "soft natural light, cheerful, bakery menu photo, no text"),
]

for i, (name, size, prompt) in enumerate(JOBS, 1):
    print(f"\n[{i}/{len(JOBS)}] {name} ...", flush=True)
    args = [PY, GEN, "--prompt", prompt, "--output", os.path.join(OUT, name + ".png"),
            "--size", size, "--count", "2"]
    if i < len(JOBS):
        args.append("--keep")          # не выключать ComfyUI между картинками
    code = subprocess.call(args)
    if code:
        print(f"Ошибка при генерации {name} (код {code})")
        sys.exit(code)

print("\nГотово! Картинки в папке:", OUT)
with open(os.path.join(OUT, "DONE.txt"), "w", encoding="utf-8") as f:
    f.write("ok")
