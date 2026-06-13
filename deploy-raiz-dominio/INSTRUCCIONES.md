# Desplegar `/agendar/` en autodealer.com.co

## Qué hace esto

La URL `https://autodealer.com.co/agendar/` dejará de mostrar 404 y redirigirá a la home del sitio nuevo con el popup **Agendar cita** abierto.

## Pasos en Hostinger (File Manager o FTP)

### 1. Subir el sitio (como siempre)

Sube **todo** el contenido del repo a `public_html/autodealer-nuevo/` (incluye la carpeta `agendar/`).

### 2. Activar la redirección en la raíz del dominio

**Opción recomendada (solo una regla):**

1. Abre `public_html/.htaccess` (raíz del dominio, no dentro de `autodealer-nuevo`).
2. Si no existe, créalo.
3. Copia el contenido de `deploy-raiz-dominio/.htaccess` de este repo.
4. Si ya tienes reglas (WordPress, etc.), pega **al inicio** del bloque `mod_rewrite`, antes de otras reglas:

```apache
RewriteRule ^agendar/?$ /autodealer-nuevo/agendar/ [R=301,L]
```

### 3. Comprobar

- `https://autodealer.com.co/agendar/` → debe ir a `.../autodealer-nuevo/agendar/` y luego abrir el modal en la home.
- En la home: botón **Agendar cita** → modal con WhatsApp Bogotá/Cali.

## Alternativa sin tocar .htaccess de la raíz

En el panel de Hostinger → **Dominios** → **Redirecciones**:

| Origen | Destino |
|--------|---------|
| `/agendar` | `https://autodealer.com.co/autodealer-nuevo/agendar/` |

Tipo: **301 permanente**.

## Enlaces en correos

Usa cualquiera de estos (todos abren el modal):

- `https://autodealer.com.co/agendar/`
- `https://autodealer.com.co/autodealer-nuevo/#agendar`
- `https://autodealer.com.co/autodealer-nuevo/agendar/`
