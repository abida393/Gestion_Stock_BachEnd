@echo off
title StockManager - Lanceur Principal
chcp 65001 > nul
color 0A

cls
echo.
echo  ##############################################################
echo  ##                                                          ##
echo  ##          STOCKMANAGER - LANCEMENT ECOSYSTEME            ##
echo  ##                                                          ##
echo  ##############################################################
echo.

REM === Chemins (modifier si besoin) ===
set BACKEND_DIR=C:\Gestion_Stock_BachEnd
set FRONTEND_DIR=C:\Gestion_Stock_FrontEnd
set AI_DIR=%BACKEND_DIR%\ai_engine

REM === Verifier les dossiers ===
echo  [CHECK] Verification des dossiers...
if not exist "%BACKEND_DIR%" ( echo  [ERREUR] Backend introuvable : %BACKEND_DIR% & pause & exit /b 1 )
if not exist "%FRONTEND_DIR%" ( echo  [ERREUR] Frontend introuvable : %FRONTEND_DIR% & pause & exit /b 1 )
if not exist "%AI_DIR%" ( echo  [ERREUR] Agent IA introuvable : %AI_DIR% & pause & exit /b 1 )
echo  [CHECK] Tous les dossiers sont presents.
echo.

REM === Verifier si les ports sont deja utilises ===
echo  [CHECK] Verification des ports disponibles...
netstat -ano | find ":8000 " | find "LISTENING" > nul 2>&1
if not errorlevel 1 (
    echo  [AVERT] Port 8000 deja utilise - le Backend est peut-etre deja lance.
) else (
    echo  [OK]    Port 8000 libre.
)

netstat -ano | find ":5000 " | find "LISTENING" > nul 2>&1
if not errorlevel 1 (
    echo  [AVERT] Port 5000 deja utilise - l'Agent IA est peut-etre deja lance.
) else (
    echo  [OK]    Port 5000 libre.
)

netstat -ano | find ":5173 " | find "LISTENING" > nul 2>&1
if not errorlevel 1 (
    echo  [AVERT] Port 5173 deja utilise - le Frontend est peut-etre deja lance.
) else (
    echo  [OK]    Port 5173 libre.
)
echo.

REM === Lancement Backend Laravel ===
echo  [1/3] Demarrage du Backend Laravel (port 8000)...
start "Backend - Laravel [http://127.0.0.1:8000]" cmd /k "color 0B && cd /d %BACKEND_DIR% && echo. && echo  [BACKEND] Laravel en cours de demarrage... && echo. && php artisan serve"
echo  [OK]    Fenetre Backend ouverte.
timeout /t 3 /nobreak > nul

REM === Lancement Agent IA Flask ===
echo  [2/3] Demarrage de l'Agent IA (port 5000)...
if exist "%AI_DIR%\venv\Scripts\python.exe" (
    start "Agent IA - Flask [http://127.0.0.1:5000]" cmd /k "color 0D && cd /d %AI_DIR% && echo. && echo  [AGENT IA] Verification de Flask... && venv\Scripts\pip install flask --quiet && echo  [AGENT IA] Demarrage sur http://127.0.0.1:5000 && echo. && venv\Scripts\python app.py"
) else (
    start "Agent IA - Flask [http://127.0.0.1:5000]" cmd /k "color 0D && cd /d %AI_DIR% && echo. && echo  [AGENT IA] Demarrage sur http://127.0.0.1:5000 && echo. && python app.py"
)
echo  [OK]    Fenetre Agent IA ouverte.
timeout /t 4 /nobreak > nul

REM === Lancement Frontend React/Vite ===
echo  [3/3] Demarrage du Frontend React/Vite (port 5173)...
start "Frontend - React/Vite [http://localhost:5173]" cmd /k "color 0E && cd /d %FRONTEND_DIR% && echo. && echo  [FRONTEND] Demarrage sur http://localhost:5173 && echo. && npm run dev"
echo  [OK]    Fenetre Frontend ouverte.

REM === Attendre que le frontend soit pret (environ 8s) ===
echo.
echo  [...] Attente du demarrage du Frontend (8 secondes)...
timeout /t 8 /nobreak > nul

REM === Ouvrir le navigateur ===
echo  [NAV]   Ouverture du navigateur sur http://localhost:5173
start "" http://localhost:5173

echo.
echo  ##############################################################
echo  ##                                                          ##
echo  ##   ECOSYSTEM STOCKMANAGER DEMARRE AVEC SUCCES !          ##
echo  ##                                                          ##
echo  ##   Backend  :  http://127.0.0.1:8000                     ##
echo  ##   Agent IA :  http://127.0.0.1:5000                     ##
echo  ##   Frontend :  http://localhost:5173                      ##
echo  ##                                                          ##
echo  ##   Pour TOUT ARRETER : lancez stop_project.bat           ##
echo  ##                                                          ##
echo  ##############################################################
echo.
pause
