@echo off
setlocal enabledelayedexpansion

set "origem=%~dp0\indicador-pessoal\*.xml"
set "destino=C:\INDICADOR"

set /a count=0
for %%F in (%origem%) do (
    copy "%%F" "%destino%" >nul
    if !errorlevel! equ 0 (
        set /a count+=1
    )
)

echo %count% > temp.txt
