@echo off
setlocal enabledelayedexpansion

set "origem=C:\ONRIPS\logsServico\*.txt"
set "destino=%~dp0\logs"

set /a count=0
for %%F in (%origem%) do (
    copy "%%F" "%destino%" >nul
    if !errorlevel! equ 0 (
        set /a count+=1
    )
)

echo %count% > temp.txt
