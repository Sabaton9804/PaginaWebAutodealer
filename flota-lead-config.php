<?php
/** Correo que recibe las solicitudes del formulario de flotas. */
$FLOTA_LEAD_EMAIL = 'servicio@autodealer.com.co';
$FLOTA_FROM_EMAIL = 'servicio@autodealer.com.co';
$FLOTA_FROM_NAME  = 'Auto Dealer — Formulario flotas';

/** SMTP Hostinger (necesario para que llegue a la bandeja) */
$FLOTA_SMTP_HOST = 'smtp.hostinger.com';
$FLOTA_SMTP_PORT = 587;
$FLOTA_SMTP_USER = 'servicio@autodealer.com.co';
$FLOTA_SMTP_PASS = '';

if (is_readable(__DIR__ . '/flota-lead-secrets.php')) {
	require __DIR__ . '/flota-lead-secrets.php';
}
