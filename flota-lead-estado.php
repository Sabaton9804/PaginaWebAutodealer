<?php
/**
 * Diagnóstico rápido (no expone contraseñas).
 * Abre: https://www.autodealer.com.co/flota-lead-estado.php
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

$root = __DIR__;
$checks = array(
	'version'        => 4,
	'flota_lead_php' => is_file($root . '/flota-lead.php'),
	'flota_lead_mail'=> is_file($root . '/flota-lead-mail.php'),
	'config'         => is_readable($root . '/flota-lead-config.php'),
	'secrets'        => is_readable($root . '/flota-lead-secrets.php'),
	'log_writable'   => is_writable($root) || (is_file($root . '/flota-lead.log') && is_writable($root . '/flota-lead.log')),
);

$smtp_port   = 465;
$smtp_secure = 'ssl';
$smtp_user   = '';
$smtp_pass_set = false;

if ($checks['config']) {
	require $root . '/flota-lead-config.php';
	$smtp_port     = isset($FLOTA_SMTP_PORT) ? (int) $FLOTA_SMTP_PORT : 465;
	$smtp_secure   = isset($FLOTA_SMTP_SECURE) ? (string) $FLOTA_SMTP_SECURE : 'ssl';
	$smtp_user     = isset($FLOTA_SMTP_USER) ? (string) $FLOTA_SMTP_USER : '';
	$smtp_pass_set = ! empty($FLOTA_SMTP_PASS);
}

$checks['smtp_user']     = $smtp_user;
$checks['smtp_pass_set'] = $smtp_pass_set;
$checks['smtp_port']     = $smtp_port;
$checks['smtp_secure']   = $smtp_secure;
$checks['listo']         = $checks['flota_lead_php'] && $checks['flota_lead_mail'] && $checks['secrets'] && $smtp_pass_set;

if (is_readable($root . '/flota-lead.log')) {
	$lines = @file($root . '/flota-lead.log', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	$checks['ultimas_lineas_log'] = $lines ? array_slice($lines, -5) : array();
}

echo json_encode($checks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
