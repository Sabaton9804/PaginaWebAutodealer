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

- `https://www.autodealer.com.co/autodealer-nuevo/flota-lead.php`  
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

## Si ya no hay 404 pero no llega el correo

- Revisa **spam** en `servicio@autodealer.com.co`
- En Hostinger, el `mail()` de PHP a veces falla; avisa para configurar SMTP.
