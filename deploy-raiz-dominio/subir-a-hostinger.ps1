# Copia el paquete de raíz del dominio a una ruta local de public_html (FTP sincronizado).
# Uso: .\deploy-raiz-dominio\subir-a-hostinger.ps1 -Destino "C:\ruta\a\public_html"

param(
  [Parameter(Mandatory = $true)]
  [string]$Destino
)

$ErrorActionPreference = "Stop"
$Origen = Split-Path -Parent $MyInvocation.MyCommand.Path

if (-not (Test-Path $Destino)) {
  New-Item -ItemType Directory -Path $Destino -Force | Out-Null
}

# Carpeta agendar en la raíz (plan B si no puedes editar .htaccess)
$agendarDest = Join-Path $Destino "agendar"
New-Item -ItemType Directory -Path $agendarDest -Force | Out-Null
Copy-Item -Path (Join-Path $Origen "agendar\index.html") -Destination (Join-Path $agendarDest "index.html") -Force

# Fragmento .htaccess (plan A — fusionar manualmente si ya existe .htaccess)
$htDest = Join-Path $Destino ".htaccess"
$htOrigen = Join-Path $Origen ".htaccess"
if (-not (Test-Path $htDest)) {
  Copy-Item -Path $htOrigen -Destination $htDest -Force
  Write-Host "Creado: $htDest"
} else {
  $snippet = Get-Content $htOrigen -Raw
  $existing = Get-Content $htDest -Raw
  if ($existing -notmatch "autodealer-nuevo/agendar") {
    $merged = $snippet.Trim() + "`r`n`r`n" + $existing
    Set-Content -Path $htDest -Value $merged -Encoding UTF8
    Write-Host "Reglas agendar añadidas al inicio de: $htDest"
  } else {
    Write-Host "Ya existen reglas agendar en: $htDest"
  }
}

Write-Host ""
Write-Host "Listo. Sube tambien public_html/autodealer-nuevo/ desde el repo (FTP)."
Write-Host "Prueba: https://www.autodealer.com.co/agendar/"
