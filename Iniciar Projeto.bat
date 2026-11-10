@echo off
setlocal

set "ROOT=%~dp0"
set "API_DIR=%ROOT%TesteTrindtechAPI"
set "FRONTEND_DIR=%ROOT%TesteTrindtechFrontend"

echo Verificando os pre-requisitos...
where php >nul 2>nul
if errorlevel 1 (
    echo PHP nao foi encontrado no PATH.
    goto :failed
)
where composer >nul 2>nul
if errorlevel 1 (
    echo Composer nao foi encontrado no PATH.
    goto :failed
)
where node >nul 2>nul
if errorlevel 1 (
    echo Node.js nao foi encontrado no PATH.
    goto :failed
)
where npm >nul 2>nul
if errorlevel 1 (
    echo npm nao foi encontrado no PATH.
    goto :failed
)
php -r "if (PHP_VERSION_ID < 80300 || !extension_loaded('pdo_mysql') || !extension_loaded('curl')) { fwrite(STDERR, 'Use PHP 8.3+ com as extensoes pdo_mysql e curl habilitadas.'.PHP_EOL); exit(1); }"
if errorlevel 1 goto :failed
node -e "const [major, minor] = process.versions.node.split('.').map(Number); if (!((major === 20 && minor >= 19) || (major === 22 && minor >= 12) || major > 22)) process.exit(1);"
if errorlevel 1 (
    echo Vite requer Node.js 20.19+ ou 22.12+.
    goto :failed
)

if not exist "%API_DIR%\composer.json" (
    echo Nao encontrei o projeto da API em "%API_DIR%".
    goto :failed
)
if not exist "%FRONTEND_DIR%\package.json" (
    echo Nao encontrei o projeto frontend em "%FRONTEND_DIR%".
    goto :failed
)

if not exist "%API_DIR%\vendor\autoload.php" (
    echo Instalando dependencias da API...
    pushd "%API_DIR%"
    call composer install --no-interaction --prefer-dist
    if errorlevel 1 (
        popd
        goto :failed
    )
    popd
)

if not exist "%API_DIR%\.env" (
    echo Criando o arquivo de ambiente da API...
    copy "%API_DIR%\.env.example" "%API_DIR%\.env" >nul
    if errorlevel 1 goto :failed
)

findstr /r /c:"^APP_KEY=." "%API_DIR%\.env" >nul
if errorlevel 1 (
    echo Gerando a chave da aplicacao...
    pushd "%API_DIR%"
    call php artisan key:generate --force --no-interaction
    if errorlevel 1 (
        popd
        goto :failed
    )
    popd
)

if not exist "%FRONTEND_DIR%\node_modules\vite\bin\vite.js" (
    echo Instalando dependencias do frontend...
    pushd "%FRONTEND_DIR%"
    call npm.cmd ci --no-audit --no-fund
    if errorlevel 1 (
        popd
        goto :failed
    )
    popd
)

echo Preparando o banco de dados...
pushd "%API_DIR%"
call php scripts\ensure-mysql-database.php
if errorlevel 1 (
    popd
    goto :failed
)
call php artisan migrate --seed --force --no-interaction
if errorlevel 1 (
    popd
    goto :failed
)
popd

set "VITE_API_URL=http://127.0.0.1:8000/api"
echo Iniciando API e frontend...
start "Trindtech API" /D "%API_DIR%" cmd /k "php artisan serve --host=127.0.0.1 --port=8000"
start "Trindtech Frontend" /D "%FRONTEND_DIR%" cmd /k "npm.cmd run dev -- --host 127.0.0.1 --port 5173 --strictPort"

echo Aguardando os servidores iniciarem...
powershell -NoProfile -ExecutionPolicy Bypass -Command "$deadline = (Get-Date).AddSeconds(90); do { $ready = $true; foreach ($url in @('http://127.0.0.1:8000/api', 'http://127.0.0.1:5173')) { try { $null = Invoke-WebRequest -UseBasicParsing -Uri $url -TimeoutSec 2 } catch { $ready = $false; break } }; if ($ready) { exit 0 }; Start-Sleep -Seconds 2 } while ((Get-Date) -lt $deadline); exit 1"
if errorlevel 1 (
    echo Os servidores nao responderam no tempo esperado. Confira as janelas da API e do frontend.
    goto :failed
)

start "" "http://127.0.0.1:5173"
echo Projeto pronto em http://127.0.0.1:5173
echo Login: admin@trindtech.com
echo Senha: senha123
echo Feche as janelas "Trindtech API" e "Trindtech Frontend" para parar os servidores.
exit /b 0

:failed
echo.
echo Inicializacao interrompida. Confira os erros acima e os pre-requisitos no README.md.
pause
exit /b 1
