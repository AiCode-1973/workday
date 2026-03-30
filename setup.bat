@echo off
REM ============================================================
REM  Workday — Script de Setup (Windows / XAMPP)
REM  Uso: Clique duas vezes ou execute como Administrador
REM ============================================================

SETLOCAL

SET MYSQL_BIN=C:\xampp1\mysql\bin\mysql.exe
SET DB_HOST=127.0.0.1
SET DB_PORT=3306
SET DB_USER=root
SET DB_PASS=
SET DB_NAME=workday
SET PROJECT_DIR=%~dp0

ECHO.
ECHO =============================================
ECHO   Workday - Configuracao do Banco de Dados
ECHO =============================================
ECHO.

REM Verifica se o MySQL existe
IF NOT EXIST "%MYSQL_BIN%" (
    ECHO [ERRO] MySQL nao encontrado em %MYSQL_BIN%
    ECHO   Ajuste a variavel MYSQL_BIN neste script.
    PAUSE
    EXIT /B 1
)

ECHO [1/4] Criando banco de dados '%DB_NAME%'...
"%MYSQL_BIN%" -h %DB_HOST% -P %DB_PORT% -u %DB_USER% -e "CREATE DATABASE IF NOT EXISTS `%DB_NAME%` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
IF ERRORLEVEL 1 (
    ECHO [ERRO] Falha ao criar o banco. Verifique se o MariaDB/MySQL esta rodando.
    PAUSE
    EXIT /B 1
)
ECHO    OK

ECHO [2/4] Executando migrations (001_schema.sql)...
"%MYSQL_BIN%" -h %DB_HOST% -P %DB_PORT% -u %DB_USER% %DB_NAME% < "%PROJECT_DIR%database\migrations\001_schema.sql"
IF ERRORLEVEL 1 (
    ECHO [ERRO] Falha na migration do schema.
    PAUSE
    EXIT /B 1
)
ECHO    OK

ECHO [3/4] Executando seeds (002_seeds.sql)...
"%MYSQL_BIN%" -h %DB_HOST% -P %DB_PORT% -u %DB_USER% %DB_NAME% < "%PROJECT_DIR%database\migrations\002_seeds.sql"
IF ERRORLEVEL 1 (
    ECHO [AVISO] Falha nos seeds (podem ja existir). Continuando...
)
ECHO    OK

ECHO [4/4] Criando pasta uploads...
IF NOT EXIST "%PROJECT_DIR%public\uploads" MKDIR "%PROJECT_DIR%public\uploads"
ECHO    OK

ECHO.
ECHO =============================================
ECHO   Setup concluido com sucesso!
ECHO =============================================
ECHO.
ECHO   Acesse: http://localhost/workday
ECHO.
ECHO   Credenciais de demo:
ECHO     admin@workday.app  /  password
ECHO     joao@workday.app   /  password
ECHO     ana@workday.app    /  password
ECHO.
ECHO   Para WebSocket (terminal separado):
ECHO     php websocket_server.php
ECHO.
PAUSE
