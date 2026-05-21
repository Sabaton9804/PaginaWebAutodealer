<?php
/**
 * Recibe solicitudes de cotización flotas (HTML estático) y envía correo.
 * Ubicación recomendada en el servidor: public_html/autodealer-nuevo/flota-lead.php
 */
declare(strict_types=1);

function flota_lead_cors(): void {
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: POST, OPTIONS');
	header('Access-Control-Allow-Headers: Content-Type');
	header('Content-Type: application/json; charset=UTF-8');
}

flota_lead_cors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(204);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode(array('ok' => false, 'error' => 'Método no permitido'));
	exit;
}

require_once __DIR__ . '/flota-lead-mail.php';

$config_file = __DIR__ . '/flota-lead-config.php';
$to          = 'servicio@autodealer.com.co';
$from_email  = 'servicio@autodealer.com.co';
$from_name   = 'Auto Dealer — Formulario flotas';
$smtp_host    = 'smtp.hostinger.com';
$smtp_port    = 465;
$smtp_secure  = 'ssl';
$smtp_user    = 'servicio@autodealer.com.co';
$smtp_pass    = '';
$flota_version = 4;

if (is_readable($config_file)) {
	require $config_file;
	if (isset($FLOTA_LEAD_EMAIL) && filter_var($FLOTA_LEAD_EMAIL, FILTER_VALIDATE_EMAIL)) {
		$to = $FLOTA_LEAD_EMAIL;
	}
	if (! empty($FLOTA_FROM_EMAIL) && filter_var($FLOTA_FROM_EMAIL, FILTER_VALIDATE_EMAIL)) {
		$from_email = $FLOTA_FROM_EMAIL;
	}
	if (! empty($FLOTA_FROM_NAME)) {
		$from_name = (string) $FLOTA_FROM_NAME;
	}
	if (! empty($FLOTA_SMTP_HOST)) {
		$smtp_host = (string) $FLOTA_SMTP_HOST;
	}
	if (! empty($FLOTA_SMTP_PORT)) {
		$smtp_port = (int) $FLOTA_SMTP_PORT;
	}
	if (! empty($FLOTA_SMTP_SECURE)) {
		$smtp_secure = (string) $FLOTA_SMTP_SECURE;
	}
	if (! empty($FLOTA_SMTP_USER)) {
		$smtp_user = (string) $FLOTA_SMTP_USER;
	}
	if (isset($FLOTA_SMTP_PASS) && $FLOTA_SMTP_PASS !== '') {
		$smtp_pass = (string) $FLOTA_SMTP_PASS;
	}
}

$params = $_POST;
if (! $params) {
	$raw = file_get_contents('php://input');
	if ($raw) {
		$json = json_decode($raw, true);
		if (is_array($json)) {
			$params = $json;
		} else {
			parse_str($raw, $parsed);
			if ($parsed) {
				$params = $parsed;
			}
		}
	}
}
if (! is_array($params)) {
	$params = array();
}

/* Campo trampa: antes se llamaba "company" y el autocompletado del navegador lo llenaba → ok sin correo */
$honeypot = isset($params['_flota_hp']) ? trim((string) $params['_flota_hp']) : '';
if ($honeypot !== '') {
	flota_lead_log('SKIP honeypot len=' . strlen($honeypot));
	echo json_encode(array('ok' => true));
	exit;
}

$nombre = isset($params['nombre']) ? trim((string) $params['nombre']) : '';
$email  = isset($params['email']) ? trim((string) $params['email']) : '';
$tel    = isset($params['tel']) ? trim((string) $params['tel']) : '';
$size   = isset($params['size']) ? trim((string) $params['size']) : '';

if ($nombre === '' || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || $tel === '' || $size === '') {
	http_response_code(400);
	echo json_encode(array('ok' => false, 'error' => 'Completa todos los campos con datos válidos.'));
	exit;
}

$ip = '0';
if (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
	$parts = explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']);
	$ip    = trim($parts[0]);
} elseif (! empty($_SERVER['REMOTE_ADDR'])) {
	$ip = (string) $_SERVER['REMOTE_ADDR'];
}

$subject = sprintf('[AutoDealer Flotas] Nueva solicitud de %s', $nombre);
$body    = sprintf(
	"Nueva solicitud de cotización (flotas)\n\nNombre: %s\nCorreo: %s\nTeléfono: %s\nTamaño de flota: %s\n\nIP: %s\nOrigen: landing autodealer-nuevo\n",
	$nombre,
	$email,
	$tel,
	$size,
	$ip
);

$secrets_candidates = array(
	__DIR__ . '/flota-lead-secrets.php',
	__DIR__ . '/autodealer-nuevo/flota-lead-secrets.php',
	dirname(__DIR__) . '/flota-lead-secrets.php',
);
$secrets_file       = null;
foreach ($secrets_candidates as $candidate) {
	if (is_readable($candidate)) {
		$secrets_file = $candidate;
		break;
	}
}
$host_check = isset($_SERVER['HTTP_HOST']) ? strtolower((string) $_SERVER['HTTP_HOST']) : '';
$is_prod    = (strpos($host_check, 'autodealer.com.co') !== false);

if ($is_prod && ($secrets_file === null || $smtp_pass === '')) {
	http_response_code(503);
	echo json_encode(array(
		'ok'    => false,
		'v'     => $flota_version,
		'error' => 'Falta flota-lead-secrets.php en public_html con la contraseña SMTP de servicio@autodealer.com.co',
	));
	exit;
}

$result = flota_lead_send_mail(
	$to,
	$subject,
	$body,
	$email,
	$from_email,
	$from_name,
	$smtp_host,
	$smtp_port,
	$smtp_secure,
	$smtp_user,
	$smtp_pass !== '' ? $smtp_pass : null
);

if (! $result['ok']) {
	http_response_code(500);
	$err = isset($result['error']) ? $result['error'] : 'No se pudo enviar el correo.';
	echo json_encode(array(
		'ok'    => false,
		'v'     => $flota_version,
		'via'   => isset($result['via']) ? $result['via'] : null,
		'error' => $err . ' Escríbenos a servicio@autodealer.com.co',
	));
	exit;
}

echo json_encode(array(
	'ok'  => true,
	'v'   => $flota_version,
	'via' => isset($result['via']) ? $result['via'] : 'unknown',
));
