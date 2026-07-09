<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MLPP_Templates {

	public function get_all(): array {
		$templates = [
			'newsletter'    => $this->newsletter(),
			'whatsapp'      => $this->whatsapp(),
			'coupon'        => $this->coupon(),
			'event'         => $this->event(),
			'notice'        => $this->notice(),
			'image_campaign'=> $this->image_campaign(),
			'fullscreen'    => $this->fullscreen_launch(),
			'lead_capture'  => $this->lead_capture(),
			'black_friday'  => $this->black_friday(),
			'christmas'     => $this->christmas(),
			'exit_survey'   => $this->exit_survey(),
			'free_shipping' => $this->free_shipping(),
		];

		/**
		 * Filters the registry of available popup templates.
		 * Add custom templates (e.g. via a theme or companion plugin) by
		 * appending entries to the array. Each entry must expose at minimum
		 * `id`, `label`, `icon`, `popup_type`, `title`, `body` and `design`.
		 *
		 * @param array $templates Map of template_id => template_data.
		 */
		return (array) apply_filters( 'mlpp_templates', $templates );
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

	private function black_friday(): array {
		return [
			'id'          => 'black_friday',
			'label'       => 'Black Friday',
			'icon'        => '🛍',
			'popup_type'  => 'center_modal',
			'title'       => 'Black Friday: até 70% OFF',
			'subtitle'    => 'Ofertas por tempo limitado',
			'body'        => '<p>A maior Black Friday da nossa história começou. Aproveite descontos exclusivos em toda a loja, somente até domingo.</p>',
			'btn_primary_text'  => 'Aproveitar agora',
			'btn_secondary_text'=> 'Agora não',
			'design'      => [
				'bg_color'      => '#0a0a0a',
				'text_color'    => '#ffffff',
				'btn_color'     => '#facc15',
				'btn_text_color'=> '#0a0a0a',
				'animation_type'=> 'zoom',
			],
		];
	}

	private function christmas(): array {
		return [
			'id'          => 'christmas',
			'label'       => 'Natal',
			'icon'        => '🎄',
			'popup_type'  => 'center_modal',
			'title'       => 'Feliz Natal! Ganhe 15% OFF',
			'subtitle'    => 'Presenteie quem você ama',
			'body'        => '<p>Use o cupom <strong>NATAL15</strong> e ganhe 15% de desconto em toda a loja até 25 de dezembro.</p>',
			'btn_primary_text'  => 'Pegar cupom',
			'btn_secondary_text'=> 'Continuar sem cupom',
			'design'      => [
				'bg_color'      => '#7f1d1d',
				'text_color'    => '#fff7ed',
				'btn_color'     => '#16a34a',
				'btn_text_color'=> '#ffffff',
				'animation_type'=> 'slide_down',
			],
		];
	}

	private function exit_survey(): array {
		return [
			'id'          => 'exit_survey',
			'label'       => 'Pesquisa de Saída',
			'icon'        => '💬',
			'popup_type'  => 'center_modal',
			'title'       => 'Antes de ir, nos conte',
			'subtitle'    => 'Sua opinião leva 5 segundos',
			'body'        => '<p>O que te impediu de comprar hoje? Sua resposta nos ajuda a melhorar — sem cadastro, sem compromisso.</p>',
			'btn_primary_text'  => 'Responder agora',
			'btn_secondary_text'=> 'Sair sem responder',
			'design'      => [
				'bg_color'      => '#ffffff',
				'text_color'    => '#0f172a',
				'btn_color'     => '#0ea5e9',
				'animation_type'=> 'fade',
			],
		];
	}

	private function free_shipping(): array {
		return [
			'id'          => 'free_shipping',
			'label'       => 'Frete Grátis',
			'icon'        => '🚚',
			'popup_type'  => 'bottom_bar',
			'title'       => 'Frete grátis acima de R$ 199',
			'subtitle'    => 'Para todo o Brasil',
			'body'        => '<p>Adicione mais <strong>R$ 50</strong> ao seu carrinho e ganhe frete grátis. Válido para todas as regiões.</p>',
			'btn_primary_text'  => 'Continuar comprando',
			'design'      => [
				'bg_color'      => '#fef3c7',
				'text_color'    => '#78350f',
				'btn_color'     => '#b45309',
				'animation_type'=> 'slide_up',
			],
		];
	}
}
