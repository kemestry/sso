<?php
/**
 * Provide a Contact Profile
 */

namespace App\Controller\oAuth2;

class Profile extends \OpenTHC\Controller\Base
{
	function __invoke($REQ, $RES, $ARG)
	{
		$dbc_auth = $this->_container->DBC_AUTH;
		$dbc_main = $this->_container->DBC_MAIN;

		$Profile = array(
			'scope' => [],
			'Contact' => [],
			'Company' => [],
		);

		$auth = preg_match('/^Bearer ([\w\-]+)$/', $_SERVER['HTTP_AUTHORIZATION'], $m) ? $m[1] : null;
		if (empty($auth)) {
			return $RES->withJSON([
				'meta' => [ 'detail' => 'Invalid Request [COP#022]' ]
			], 403);
		}

		// Find Bearer Token
		$sql = 'SELECT id, meta FROM auth_context_ticket WHERE id = ?';
		$arg = array($auth);
		$tok = $dbc_auth->fetchRow($sql, $arg);
		if (empty($tok)) {
			return $RES->withJSON([
				'meta' => ['detail' => 'Invalid Token [COP#030]' ]
			], 400);
		}

		// Find Bearer Token
		$tok['meta'] = json_decode($tok['meta'], true);

		// Auth/Contact
		$sql = 'SELECT id, username FROM auth_contact WHERE id = ?';
		$arg = array($tok['meta']['contact_id']);
		$Contact = $dbc_auth->fetchRow($sql, $arg);
		if (empty($Contact['id'])) {
			return $RES->withJSON([
				'meta' => ['detail' => 'Invalid Token [COP#033]' ],
			], 400);
		}

		$RES = $RES->withAttribute('Contact', $Contact);

		$Profile['Contact']['id'] = $Contact['id'];
		$Profile['Contact']['username'] = $Contact['username'];

		// Auth/Company
		$sql = 'SELECT id, name FROM auth_company WHERE id = ?';
		$arg = [ $tok['meta']['company_id'] ];
		$res = $dbc_auth->fetchRow($sql, $arg);
		if (!empty($res['id'])) {
			$Profile['Company']['id'] = $res['id'];
			// $Profile['Company']['ulid'] = $res['id']; // @deprecated
			// $Profile['Company']['guid'] = $res['guid'];
			$Profile['Company']['name'] = $res['name'];
			// $Profile['Company']['type'] = $res['type'];
		}
		$RES = $RES->withAttribute('Company', $Company);

		// Scope
		$Profile['scope'] = explode(' ', $tok['meta']['scope']);

		// Main/Contact
		$sql = 'SELECT * FROM contact WHERE id = ?';
		$arg = array($Contact['id']);
		$res = $dbc_main->fetchRow($sql, $arg);
		if (!empty($res['id'])) {

			$Profile['Contact']['fullname'] = $res['fullname'];

			if (!empty($res['email'])) {
				$Profile['Contact']['email'] = true;
			}
			if (!empty($res['phone'])) {
				$Profile['Contact']['phone'] = true;
			}

		}

		return $RES->withJSON($Profile);

	}
}
