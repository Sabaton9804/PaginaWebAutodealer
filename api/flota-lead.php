<?php
/**
 * Recibe solicitudes de cotización flotas (HTML estático) y envía correo.
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

$config_file = dirname(__DIR__) . '/flota-lead-config.php';
$to          = 'servicio@autodealer.com.co';
if (is_readable($config_file)) {
	require $config_file;
	if (isset($FLOTA_LEAD_EMAIL) && filter_var($FLOTA_LEAD_EMAIL, FILTER_VALIDATE_EMAIL)) {
		$to = $FLOTA_LEAD_EMAIL;
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

$honeypot = isset($params['company']) ? trim((string) $params['company']) : '';
if ($honeypot !== '') {
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

$headers = array(
	'Content-Type: text/plain; charset=UTF-8',
	'Reply-To: ' . $email,
);

$sent = @mail($to, $subject, $body, implode("\r\n", $headers));
if (! $sent) {
	http_response_code(500);
	echo json_encode(array('ok' => false, 'error' => 'No se pudo enviar el correo. Escríbenos a servicio@autodealer.com.co'));
	exit;
}

echo json_encode(array('ok' => true));
