<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MLPP_Templates {

	public function get_all(): array {
		return [
			'newsletter'    => $this->newsletter(),
			'whatsapp'      => $this->whatsapp(),
			'coupon'        => $this->coupon(),
			'event'         => $this->event(),
			'notice'        => $this->notice(),
			'image_campaign'=> $this->image_campaign(),
			'fullscreen'    => $this->fullscreen_launch(),
			'lead_capture'  => $this->lead_capture(),
		];
	}

	public function get( string $id ): ?array {
		$all = $this->get_all();
		return $all[ $id ] ?? null;
	}

	private function newsletter(): array {
		return [
			'id'          => 'newsletter',
			'label'       => 'Newsletter Signup',
			'icon'        => '✉️',
			'popup_type'  => 'center_modal',
			'title'       => 'Fique por dentro das novidades',
			'subtitle'    => 'Assine nossa newsletter gratuita',
			'body'        => '<p>Receba conteúdos exclusivos direto no seu e-mail. Sem spam, prometemos.</p>',
			'btn_primary_text'  => 'Quero assinar',
			'btn_secondary_text'=> 'Agora não',
			'design'      => [
				'bg_color'      => '#ffffff',
				'text_color'    => '#102a43',
				'btn_color'     => '#155e6f',
				'animation_type'=> 'fade',
			],
		];
	}

	private function whatsapp(): array {
		return [
			'id'          => 'whatsapp',
			'label'       => 'WhatsApp CTA',
			'icon'        => '💬',
			'popup_type'  => 'floating_box',
			'title'       => 'Fale conosco no WhatsApp',
			'subtitle'    => 'Atendimento rápido e personalizado',
			'body'        => '<p>Nossa equipe está pronta para te atender. Clique abaixo e inicie uma conversa agora.</p>',
			'btn_primary_text'  => 'Abrir WhatsApp',
			'btn_primary_url'   => 'https://wa.me/',
			'btn_secondary_text'=> 'Fechar',
			'design'      => [
				'bg_color'  => '#25d366',
				'text_color'=> '#ffffff',
				'btn_color' => '#128c7e',
			],
		];
	}

	private function coupon(): array {
		return [
			'id'          => 'coupon',
			'label'       => 'Cupom / Oferta',
			'icon'        => '🏷️',
			'popup_type'  => 'center_modal',
			'title'       => '10% OFF na sua primeira compra',
			'subtitle'    => 'Oferta por tempo limitado',
			'body'        => '<p>Use o cupom <strong>BEMVINDO10</strong> e ganhe 10% de desconto no seu pedido.</p>',
			'btn_primary_text'  => 'Usar cupom agora',
			'btn_secondary_text'=> 'Não, obrigado',
			'design'      => [
				'bg_color'      => '#fffbeb',
				'text_color'    => '#78350f',
				'btn_color'     => '#d97706',
				'animation_type'=> 'zoom',
			],
		];
	}

	private function event(): array {
		return [
			'id'          => 'event',
			'label'       => 'Anúncio de Evento',
			'icon'        => '📅',
			'popup_type'  => 'center_modal',
			'title'       => 'Evento especial chegando!',
			'subtitle'    => 'Não perca esta oportunidade',
			'body'        => '<p>Junte-se a nós em um evento incrível. Inscrições abertas com vagas limitadas.</p>',
			'btn_primary_text'  => 'Garantir minha vaga',
			'btn_secondary_text'=> 'Ver depois',
			'design'      => [
				'bg_color'      => '#f0f9ff',
				'text_color'    => '#0c4a6e',
				'btn_color'     => '#0284c7',
				'animation_type'=> 'slide_down',
			],
		];
	}

	private function notice(): array {
		return [
			'id'          => 'notice',
			'label'       => 'Aviso Simples',
			'icon'        => 'ℹ️',
			'popup_type'  => 'bottom_bar',
			'title'       => 'Aviso importante',
			'body'        => '<p>Este site utiliza cookies para melhorar sua experiência de navegação.</p>',
			'btn_primary_text'  => 'Entendi',
			'design'      => [
				'bg_color'  => '#1e293b',
				'text_color'=> '#f1f5f9',
				'btn_color' => '#38bdf8',
			],
		];
	}

	private function image_campaign(): array {
		return [
			'id'          => 'image_campaign',
			'label'       => 'Campanha com Imagem',
			'icon'        => '🖼️',
			'popup_type'  => 'center_modal',
			'title'       => 'Novidade especial para você',
			'subtitle'    => 'Confira nossa oferta exclusiva',
			'body'        => '<p>Uma oportunidade única que você não pode perder. Aproveite agora mesmo!</p>',
			'btn_primary_text'  => 'Ver oferta',
			'btn_secondary_text'=> 'Fechar',
			'design'      => [
				'bg_color'      => '#ffffff',
				'text_color'    => '#102a43',
				'btn_color'     => '#155e6f',
				'animation_type'=> 'fade',
			],
		];
	}

	private function fullscreen_launch(): array {
		return [
			'id'          => 'fullscreen',
			'label'       => 'Lançamento Fullscreen',
			'icon'        => '🚀',
			'popup_type'  => 'fullscreen_overlay',
			'title'       => 'Lançamento exclusivo!',
			'subtitle'    => 'Seja o primeiro a conhecer',
			'body'        => '<p>Nosso novo produto está chegando. Cadastre-se agora e receba acesso antecipado com condições especiais.</p>',
			'btn_primary_text'  => 'Quero acesso antecipado',
			'btn_secondary_text'=> 'Talvez depois',
			'design'      => [
				'bg_color'      => '#0f172a',
				'text_color'    => '#f8fafc',
				'btn_color'     => '#6366f1',
				'animation_type'=> 'fade',
			],
		];
	}

	private function lead_capture(): array {
		return [
			'id'          => 'lead_capture',
			'label'       => 'Captura de Lead',
			'icon'        => '🎯',
			'popup_type'  => 'slide_in',
			'title'       => 'Receba conteúdo gratuito',
			'subtitle'    => 'Material exclusivo para você',
			'body'        => '<p>Deixe seu e-mail e receba nosso e-book gratuito com dicas incríveis.</p>',
			'btn_primary_text'  => 'Quero o e-book',
			'btn_secondary_text'=> 'Não tenho interesse',
			'design'      => [
				'bg_color'      => '#f5f3ff',
				'text_color'    => '#3b0764',
				'btn_color'     => '#7c3aed',
				'animation_type'=> 'slide_up',
			],
		];
	}
}
