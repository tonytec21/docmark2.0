@echo off
setlocal enabledelayedexpansion

del "C:\MATRICULAS\100000\*.tiff"
set "origem=%~dp0\historico\*.tiff"
set "destino=C:\MATRICULAS\100000"

set /a count=0
for %%F in (%origem%) do (
    copy "%%F" "%destino%" >nul
    if !errorlevel! equ 0 (
        set /a count+=1
    )
)

echo %count% > temp.txt
