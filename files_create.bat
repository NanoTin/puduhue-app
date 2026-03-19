@echo off
setlocal EnableDelayedExpansion

REM Rutas base (relativas a la carpeta donde está este .bat)
set "WEB_DIR=apps\web-php"
set "CTRL_DIR=src\Controllers\Web"
set "SERV_DIR=src\Services"

REM Crear carpetas si no existen
if not exist "%WEB_DIR%" mkdir "%WEB_DIR%"
if not exist "%CTRL_DIR%" mkdir "%CTRL_DIR%"
if not exist "%SERV_DIR%" mkdir "%SERV_DIR%"

REM Lista de módulos
for %%M in (
    clientes
    fundos
    fundosestanques
    fundostipos
    invbodegas
    invcateganimal
    invitems
    menus
    perfiles
    prodleche
    prodlechetipos
    retiroleche
    suplanimal
    usuarios
    usuariosempresas
    usuariosfundos
) do (

    echo ==============================
    echo Modulo: %%M
    echo ==============================

    REM Construir nombre de clase con primera letra mayuscula
    set "module=%%M"
    set "first=!module:~0,1!"
    set "rest=!module:~1!"
    set "ClassName=!first!!rest!"

    REM ======================================
    REM  VISTAS PHP (apps/web-php)
    REM  (SIEMPRE SOBREESCRIBEN)
    REM ======================================

    echo Creando "%WEB_DIR%\%%M_listar.php"
    >"%WEB_DIR%\%%M_listar.php" echo ^<?php
    >>"%WEB_DIR%\%%M_listar.php" echo // Vista LISTAR de !ClassName!

    echo Creando "%WEB_DIR%\%%M_crear.php"
    >"%WEB_DIR%\%%M_crear.php" echo ^<?php
    >>"%WEB_DIR%\%%M_crear.php" echo // Vista CREAR de !ClassName!

    echo Creando "%WEB_DIR%\%%M_editar.php"
    >"%WEB_DIR%\%%M_editar.php" echo ^<?php
    >>"%WEB_DIR%\%%M_editar.php" echo // Vista EDITAR de !ClassName!

    REM ======================================
    REM  CONTROLLER (src/Controllers/Web)
    REM ======================================

    echo Creando "%CTRL_DIR%\!ClassName!Controller.php"
    >"%CTRL_DIR%\!ClassName!Controller.php" echo ^<?php
    >>"%CTRL_DIR%\!ClassName!Controller.php" echo // Controller de !ClassName!
    >>"%CTRL_DIR%\!ClassName!Controller.php" echo // TODO: implementar logica del controlador

    REM ======================================
    REM  SERVICE (src/Services)
    REM ======================================

    echo Creando "%SERV_DIR%\!ClassName!Service.php"
    >"%SERV_DIR%\!ClassName!Service.php" echo ^<?php
    >>"%SERV_DIR%\!ClassName!Service.php" echo // Service de !ClassName!
    >>"%SERV_DIR%\!ClassName!Service.php" echo // TODO: implementar logica de servicio

    echo.
)

echo ==========================================
echo Proceso terminado. Revisa las carpetas:
echo   %WEB_DIR%
echo   %CTRL_DIR%
echo   %SERV_DIR%
echo ==========================================
pause
endlocal
