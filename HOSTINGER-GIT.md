# Desplegar desde GitHub a Hostinger (automático)

Cada **push a `main`** sube todo el sitio a la **raíz de `public_html/`**  
(donde ya están `CanalFlotas/`, `flota-lead.php`, `index.html`, etc.).

## 1. Datos FTP en Hostinger

1. **hPanel** → **Archivos** → **Cuentas FTP** (o **FTP Accounts**).
2. Anota:
   - **Servidor / Host** (IP o `ftp.tudominio.com`)
   - **Usuario** (ej. `u270697239`)
   - **Contraseña**
3. Al conectar por FTP, la carpeta de destino es la **raíz del sitio** (normalmente ya estás dentro de `public_html/`).

## 2. Secrets en GitHub

Repo: https://github.com/Sabaton9804/PaginaWebAutodealer  

1. **Settings** → **Secrets and variables** → **Actions** → **New repository secret**
2. Crea tres secrets:

| Nombre | Valor |
|--------|--------|
| `FTP_SERVER` | Host del paso 1 (sin `ftp://`) |
| `FTP_USERNAME` | Usuario FTP |
| `FTP_PASSWORD` | Contraseña FTP |

## 3. Activar el despliegue

1. Haz **push** a `main` (o en GitHub: **Actions** → **Deploy to Hostinger** → **Run workflow**).
2. En **Actions** debe salir en verde **Deploy to Hostinger**.
3. Prueba:  
   `https://www.autodealer.com.co/flota-lead.php`  
   (debe decir “Método no permitido”, no 404).

## 4. Redirección `/agendar/` en la raíz del dominio

Eso **no** va en `autodealer-nuevo/`. Una vez al año sube manualmente  
`deploy-raiz-dominio/` a `public_html/` o la regla del `.htaccess` de la raíz (ver `deploy-raiz-dominio/INSTRUCCIONES.md`).

## Alternativa: Git nativo de Hostinger

hPanel → **Avanzado** → **Git** → conectar este repo y rama `main`,  
directorio de despliegue: `domains/autodealer.com.co/public_html/autodealer-nuevo`.  
Si usas solo eso, puedes desactivar el workflow de Actions.
