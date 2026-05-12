@echo off
title StockManager - Arret de l'ecosysteme
chcp 65001 > nul
color 0C

cls
echo.
echo  ##############################################################
echo  ##                                                          ##
echo  ##          STOCKMANAGER - ARRET DE L'ECOSYSTEME           ##
echo  ##                                                          ##
echo  ##############################################################
echo.
echo  Arreter tous les services ? (O/N)
set /p choix=" > "
if /i not "%choix%"=="O" ( echo Annulation. & pause & exit /b 0 )
echo.

REM === Tuer PHP artisan serve (port 8000) ===
echo  [1/3] Arret du Backend Laravel (port 8000)...
for /f "tokens=5" %%a in ('netstat -ano ^| find ":8000 " ^| find "LISTENING"') do (
    taskkill /PID %%a /F > nul 2>&1
    echo  [OK]    Processus PHP ^(PID %%a^) arrete.
)

REM === Tuer l'Agent IA Python (port 5000) ===
echo  [2/3] Arret de l'Agent IA Flask (port 5000)...
for /f "tokens=5" %%a in ('netstat -ano ^| find ":5000 " ^| find "LISTENING"') do (
    taskkill /PID %%a /F > nul 2>&1
    echo  [OK]    Processus Python ^(PID %%a^) arrete.
)

REM === Tuer Vite / Node (port 5173) ===
echo  [3/3] Arret du Frontend Vite (port 5173)...
for /f "tokens=5" %%a in ('netstat -ano ^| find ":5173 " ^| find "LISTENING"') do (
    taskkill /PID %%a /F > nul 2>&1
    echo  [OK]    Processus Node ^(PID %%a^) arrete.
)

echo.
echo  ##############################################################
echo  ##   Tous les services ont ete arretes.                     ##
echo  ##   Relancez run_project.bat pour redemarrer.              ##
echo  ##############################################################
echo.
pause
