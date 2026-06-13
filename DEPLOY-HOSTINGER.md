# Subir el envío de correo (formulario flotas) a Hostinger

Si al enviar el formulario sale **404 · api/flota-lead.php**, faltan archivos PHP en el servidor.

## Archivos obligatorios

Dentro de **`public_html/autodealer-nuevo/`** deben existir **estos tres**:

```
autodealer-nuevo/
├── flota-lead.php          ← envía el correo (OBLIGATORIO)
├── flota-lead-config.php   ← correo destino (OBLIGATORIO)
├── flota-lead.js           ← ya lo tienes si el formulario abre
├── api/
│   └── flota-lead.php      ← copia/alias (recomendado, no obligatorio si está flota-lead.php arriba)
└── CanalFlotas/
    └── index.html
```

## Comprobar en el navegador

Abre (debe **no** salir 404):

- `https://autodealer.com.co/autodealer-nuevo/flota-lead.php`  
  → puede decir "Método no permitido" (normal, solo acepta POST).

Si da **404**, el archivo **no está subido** o está en otra carpeta.

## Subir por File Manager

1. hPanel → **Archivos** → `public_html` → `autodealer-nuevo`
2. **Subir** `flota-lead.php` y `flota-lead-config.php` (desde tu PC / repo GitHub)
3. Crear carpeta `api` si no existe y subir `api/flota-lead.php`
4. Probar de nuevo el formulario en Canal Flotas

## Correo destino

En `flota-lead-config.php`:

```php
$FLOTA_LEAD_EMAIL = 'servicio@autodealer.com.co';
```

La cuenta debe existir en hPanel → **Correos**.

## Para que el correo LLEGUE a la bandeja (obligatorio)

El mensaje verde del formulario solo confirma que PHP respondió OK. En Hostinger el correo **debe** salir por SMTP.

1. En **public_html/** crea el archivo **`flota-lead-secrets.php`** (copia de `flota-lead-secrets.php.example`).
2. Pega la **contraseña** de la cuenta `servicio@autodealer.com.co` (hPanel → Correos).
3. Sube también **`flota-lead-mail.php`** y el **`flota-lead.php`** actualizado.
4. Prueba el formulario y revisa la bandeja de `servicio@autodealer.com.co` (y spam).

Sin `flota-lead-secrets.php` con contraseña, el envío suele **no llegar** aunque la web diga “recibimos tu solicitud”.

## Archivos PHP que debes tener en public_html (todos)

- `flota-lead.php` (versión 4+)
- `flota-lead-mail.php`
- `flota-lead-config.php`
- `flota-lead-secrets.php` (solo en servidor, con contraseña)
- `flota-lead-estado.php` (diagnóstico)

Comprueba: abre `https://autodealer.com.co/flota-lead-estado.php`  
Debe mostrar `"listo": true` y `"smtp_pass_set": true`.

Tras enviar el formulario, si falla revisa `flota-lead.log` en el mismo directorio.
