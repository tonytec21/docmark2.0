@echo off

setlocal enabledelayedexpansion

set input_dir=C:\laragon\www\docmark\pdf-para-tiff\historico
set output_dir=C:\laragon\www\docmark\pdf-para-tiff\pdf-viw

set "imagemagick=C:\Program Files\ImageMagick-7.1.1-Q16-HDRI\magick.exe"

set "current_pdf="
set "file_count=0"

for %%F in ("%input_dir%\*.TIF") do (
    set "filename=%%~nF"
    set "prefix=!filename:~0,8!"
    
    if not "!prefix!"=="!current_pdf!" (
        if defined current_pdf (
            echo Creating PDF: !current_pdf!
            "%imagemagick%" convert "!output_dir!\!current_pdf!*.TIF" "!output_dir!\!current_pdf!.pdf"
            del "!output_dir!\!current_pdf!*.TIF"
        )
        
        set "current_pdf=!prefix!"
        set "file_count=0"
    )
    
    set /a "file_count+=1"
    "%imagemagick%" convert "%%F" -compress jpeg "!output_dir!\!current_pdf!.!file_count!.TIF"
)

if defined current_pdf (
    echo Creating PDF: !current_pdf!
    "%imagemagick%" convert "!output_dir!\!current_pdf!*.TIF" "!output_dir!\!current_pdf!.pdf"
    del "!output_dir!\!current_pdf!*.TIF"
)

echo Conversion complete!
pause