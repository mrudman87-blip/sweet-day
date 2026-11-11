@echo off
chcp 65001 >nul
echo Generating cake images with Flux (about 3-5 minutes)...
"D:\Tools\image_venv\Scripts\python.exe" -X utf8 "%~dp0make_images.py"
echo.
pause
