<?php
declare(strict_types=1);

/**
 * Envío de correo vía SMTP Hostinger (recomendado) o mail() con From del dominio.
 */
function flota_lead_send_mail(
	string $to,
	string $subject,
	string $body,
	string $reply_to,
	string $from_email,
	string $from_name,
	?string $smtp_host,
	int $smtp_port,
	?string $smtp_user,
	?string $smtp_pass
): array {
	$from_email = trim($from_email);
	$from_name  = trim($from_name);
	$reply_to   = trim($reply_to);

	if ($smtp_pass !== null && $smtp_pass !== '' && $smtp_host && $smtp_user) {
		$err = flota_lead_smtp_send(
			$smtp_host,
			$smtp_port,
			$smtp_user,
			$smtp_pass,
			$from_email,
			$from_name,
			$to,
			$reply_to,
			$subject,
			$body
		);
		if ($err === null) {
			return array('ok' => true, 'via' => 'smtp');
		}
		return array('ok' => false, 'error' => $err, 'via' => 'smtp');
	}

	$encoded_name = $from_name !== '' ? '=?UTF-8?B?' . base64_encode($from_name) . '?=' : '';
	$from_header  = $encoded_name !== ''
		? $encoded_name . ' <' . $from_email . '>'
		: $from_email;

	$headers = array(
		'MIME-Version: 1.0',
		'Content-Type: text/plain; charset=UTF-8',
		'From: ' . $from_header,
		'Reply-To: ' . $reply_to,
	);
	$extra = '-f' . $from_email;
	$sent  = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers), $extra);
	if ($sent) {
		return array('ok' => true, 'via' => 'mail');
	}
	return array(
		'ok'    => false,
		'error' => 'mail() falló. Configura SMTP en flota-lead-secrets.php (contraseña de servicio@autodealer.com.co).',
		'via'   => 'mail',
	);
}

function flota_lead_smtp_send(
	string $host,
	int $port,
	string $user,
	string $pass,
	string $from_email,
	string $from_name,
	string $to,
	string $reply_to,
	string $subject,
	string $body
): ?string {
	$errno  = 0;
	$errstr = '';
	$fp     = @stream_socket_client(
		'tcp://' . $host . ':' . $port,
		$errno,
		$errstr,
		20,
		STREAM_CLIENT_CONNECT
	);
	if (! $fp) {
		return 'No se pudo conectar a SMTP: ' . $errstr;
	}

	stream_set_timeout($fp, 20);

	try {
		flota_smtp_expect($fp, array(220));
		flota_smtp_cmd($fp, 'EHLO autodealer.com.co');
		flota_smtp_expect($fp, array(250));

		flota_smtp_cmd($fp, 'STARTTLS');
		flota_smtp_expect($fp, array(220));
		if (! @stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
			return 'No se pudo iniciar TLS con el servidor SMTP.';
		}

		flota_smtp_cmd($fp, 'EHLO autodealer.com.co');
		flota_smtp_expect($fp, array(250));

		flota_smtp_cmd($fp, 'AUTH LOGIN');
		flota_smtp_expect($fp, array(334));
		flota_smtp_cmd($fp, base64_encode($user));
		flota_smtp_expect($fp, array(334));
		flota_smtp_cmd($fp, base64_encode($pass));
		flota_smtp_expect($fp, array(235));

		flota_smtp_cmd($fp, 'MAIL FROM:<' . $from_email . '>');
		flota_smtp_expect($fp, array(250));
		flota_smtp_cmd($fp, 'RCPT TO:<' . $to . '>');
		flota_smtp_expect($fp, array(250, 251));
		flota_smtp_cmd($fp, 'DATA');
		flota_smtp_expect($fp, array(354));

		$date    = gmdate('D, d M Y H:i:s') . ' +0000';
		$sub_enc = '=?UTF-8?B?' . base64_encode($subject) . '?=';
		$name_enc = $from_name !== '' ? '=?UTF-8?B?' . base64_encode($from_name) . '?=' : $from_email;
		$msg     = "Date: {$date}\r\n";
		$msg    .= "From: {$name_enc} <{$from_email}>\r\n";
		$msg    .= "To: <{$to}>\r\n";
		$msg    .= "Reply-To: {$reply_to}\r\n";
		$msg    .= "Subject: {$sub_enc}\r\n";
		$msg    .= "MIME-Version: 1.0\r\n";
		$msg    .= "Content-Type: text/plain; charset=UTF-8\r\n";
		$msg    .= "Content-Transfer-Encoding: 8bit\r\n";
		$msg    .= "\r\n";
		$msg    .= str_replace(array("\r\n", "\r"), "\n", $body);
		$msg     = str_replace("\n.", "\n..", $msg);
		$msg     = str_replace("\n", "\r\n", $msg);

		fwrite($fp, $msg . "\r\n.\r\n");
		flota_smtp_expect($fp, array(250));
		flota_smtp_cmd($fp, 'QUIT');
	} catch (Throwable $e) {
		fclose($fp);
		return $e->getMessage();
	}

	fclose($fp);
	return null;
}

function flota_smtp_cmd($fp, string $cmd): void {
	fwrite($fp, $cmd . "\r\n");
}

/** @param array<int> $codes */
function flota_smtp_expect($fp, array $codes): void {
	$line = '';
	while (($buf = fgets($fp, 515)) !== false) {
		$line .= $buf;
		if (isset($buf[3]) && $buf[3] === ' ') {
			break;
		}
	}
	$code = (int) substr($line, 0, 3);
	if (! in_array($code, $codes, true)) {
		throw new RuntimeException('SMTP inesperado: ' . trim($line));
	}
}
