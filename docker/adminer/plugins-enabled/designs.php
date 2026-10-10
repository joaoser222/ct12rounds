<?php

// Theme-switcher do Adminer com suporte a sub-path (/adminer).
//
// O plugin oficial "designs" gera o href do CSS a partir da chave crua do
// array, sem passar por baseUrl. Atras do `handle_path /adminer` do Caddy o
// container serve os arquivos na raiz, mas o browser os pede sob /adminer/.
// Este wrapper estende AdminerDesigns e usa chaves prefixadas com /adminer
// para que o select e os <link> apontem para o path correto do browser. O
// css() desprefixa antes de delegar ao plugin, que assim encontra o arquivo
// real (e o file_get_contents do dark-detection) dentro do container.
require_once '/var/www/html/plugins/designs.php';

class AdminerDesignsPrefixed extends AdminerDesigns {
	const PREFIX = '/adminer';

	public function __construct(array $designs) {
		$prefixed = array();
		foreach ($designs as $url => $name) {
			$prefixed[self::PREFIX . $url] = $name;
		}
		parent::__construct($prefixed);
	}

	// O afterConnect() do plugin so roda apos conectar ao DB. No POST de troca de
	// tema a senha nao vai em $_POST, entao Driver::connect falha com
	// "Connection refused" e a sessao perde o design. Persiste o tema sem exigir
	// conexao e redireciona mantendo a query (pgsql/db/username/ns) para nao cair
	// no login e perder a sessao.
	function afterConnect() {
		if (isset($_POST["design"]) && Adminer\verify_token()) {
			Adminer\restart_session();
			$_SESSION["design"] = $_POST["design"];
			session_write_close();
			Adminer\redirect($_SERVER["REQUEST_URI"]);
		}
	}

	public function css() {
		$design = $_SESSION["design"] ?? '';
		// A sessao guarda a chave prefixada (valor do <option>). O file_get_contents
		// e o regex de dark-detection precisam do path real do container. O webroot
		// e /var/www/html, entao resolve contra ele em vez da raiz do filesystem.
		$real = '/var/www/html/' . ltrim($this->unprefix($design), '/');

		if (array_key_exists($design, $this->designs) && is_readable($real)) {
			$mode = str_contains($real, '-dark')
				? 'dark'
				: (preg_match('~prefers-color-scheme:\s*dark~', file_get_contents($real)) ? '' : 'light');

			// Devolve a chave prefixada para o <link> apontar ao path do browser.
			return array($design => $mode);
		}

		// Nenhum design custom (primeira visita ou design nativo): mantém o
		// comportamento original, sem sobrescrever o css() do core.
		return array();
	}

	private function unprefix(string $url): string {
		if (str_starts_with($url, self::PREFIX . '/')) {
			return substr($url, strlen(self::PREFIX));
		}
		return $url;
	}
}

// Hidra (custom, servido de /var/www/html/adminer-hydra.css) e o tema padrao.
// Os demais vem do image bundled em /var/www/html/designs/<nome>/<arquivo>.
$designs = array(
	'/adminer-hydra.css'              => 'Hydra',
	'/designs/adminer-dark/adminer-dark.css' => 'Default Dark',
	'/designs/dracula/adminer-dark.css'      => 'Dracula',
	'/designs/galkaev/adminer-dark.css'      => 'Galkaev',
	'/designs/mancave/adminer-dark.css'      => 'Mancave',
	'/designs/rmsoft_blue-dark/adminer.css'  => 'RMSoft Blue Dark',
	'/designs/nette/adminer.css'             => 'Nette',
	'/designs/hever/adminer.css'             => 'Hever',
	'/designs/cpanel/adminer.css'            => 'cPanel',
	'/designs/brade/adminer.css'             => 'Brade',
	'/designs/ng9/adminer.css'               => 'NG9',
	'/designs/pepa-linha/adminer.css'        => 'Pepa Linha',
	'/designs/lucas-sandery/adminer.css'     => 'Lucas Sandery',
	'/designs/win98/adminer.css'             => 'Win98',
	'/designs/pappu687/adminer.css'          => 'Pappu687',
	'/designs/mvt/adminer.css'               => 'MVT',
	'/designs/lavender-light/adminer.css'    => 'Lavender Light',
	'/designs/rmsoft/adminer.css'            => 'RMSoft',
	'/designs/rmsoft_blue/adminer.css'       => 'RMSoft Blue',
	'/designs/price/adminer.css'             => 'Price',
	'/designs/flat/adminer.css'              => 'Flat',
	'/designs/haeckel/adminer.css'           => 'Haeckel',
	'/designs/pokorny/adminer.css'           => 'Pokorny',
	'/designs/paranoiq/adminer.css'          => 'Paranoiq',
	'/designs/bueltge/adminer.css'           => 'Bültge',
	'/designs/esterka/adminer.css'           => 'Esterka',
	'/designs/nicu/adminer.css'              => 'Nicu',
	'/designs/konya/adminer.css'             => 'Konya',
	'/designs/adminer-border/adminer.css'    => 'Border',
);

// Semeia o tema padrao (hidra) na primeira visita, sem sobrescrever escolha do
// usuario. A sessao guarda a chave prefixada, igual ao valor do <option>.
if (!isset($_SESSION['design'])) {
	$_SESSION['design'] = AdminerDesignsPrefixed::PREFIX . '/adminer-hydra.css';
}

return new AdminerDesignsPrefixed($designs);
