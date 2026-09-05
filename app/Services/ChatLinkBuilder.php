<?php

namespace App\Services;

class ChatLinkBuilder
{
    public const DEFAULT_TEMPLATE = "Hi! I'm interested in {product} {denomination} {price}.\n{url}";

    public function __construct(
        protected bool $whatsappOn = false,
        protected string $whatsappNumberRaw = '',
        protected string $whatsappTemplateRaw = '',
        protected bool $messengerOn = false,
        protected string $messengerUsernameRaw = '',
    ) {
    }

    /**
     * The only place in this class that touches application settings.
     * Everything else is pure so it can be unit tested without a database.
     */
    public static function fromSettings(): self
    {
        return new self(
            (bool) site_setting('product_chat_whatsapp_enabled', false),
            (string) site_setting('product_chat_whatsapp_number', ''),
            (string) site_setting('product_chat_whatsapp_template', self::DEFAULT_TEMPLATE),
            (bool) site_setting('product_chat_messenger_enabled', false),
            (string) site_setting('product_chat_messenger_username', ''),
        );
    }

    public function whatsappNumber(): string
    {
        return preg_replace('/\D/', '', $this->whatsappNumberRaw) ?? '';
    }

    public function messengerUsername(): string
    {
        $username = trim($this->messengerUsernameRaw);
        $username = preg_replace('#^https?://#i', '', $username);
        $username = preg_replace('#^(www\.)?(m\.me|messenger\.com|facebook\.com)/#i', '', $username);
        $username = ltrim($username, '@');

        return trim($username, '/');
    }

    public function whatsappEnabled(): bool
    {
        return $this->whatsappOn && $this->whatsappNumber() !== '';
    }

    public function messengerEnabled(): bool
    {
        return $this->messengerOn && $this->messengerUsername() !== '';
    }

    public function enabled(): bool
    {
        return $this->whatsappEnabled() || $this->messengerEnabled();
    }

    public function messageTemplate(): string
    {
        $template = trim($this->whatsappTemplateRaw);

        return $template !== '' ? $template : self::DEFAULT_TEMPLATE;
    }

    public function renderMessage(array $replacements): string
    {
        return self::normalise(strtr($this->messageTemplate(), $replacements));
    }

    /**
     * Collapses horizontal whitespace left behind by empty tokens without
     * destroying the newlines the template author wrote on purpose.
     */
    public static function normalise(string $message): string
    {
        $message = preg_replace('/[ \t]+/', ' ', $message);
        $message = preg_replace('/ +([.,!?;:])/', '$1', $message);

        $lines = array_filter(
            array_map('trim', preg_split('/\R/', $message)),
            static fn (string $line): bool => $line !== '',
        );

        return trim(implode("\n", $lines));
    }

    public static function formatPrice(float|int|string|null $price): string
    {
        if ($price === null || $price === '') {
            return '';
        }

        return '৳' . number_format((float) $price, 0);
    }

    /**
     * Facebook rejects arbitrary ref strings, so fold everything outside
     * [A-Za-z0-9_] and cap the result.
     */
    public static function refToken(string $slug, string $denomination = ''): string
    {
        $raw = 'product_' . $slug . ($denomination !== '' ? '_' . $denomination : '');
        $token = preg_replace('/[^A-Za-z0-9_]+/', '_', $raw);
        $token = preg_replace('/_+/', '_', $token);

        return substr(trim($token, '_'), 0, 255);
    }

    public function whatsappUrl(string $message): string
    {
        $url = 'https://wa.me/' . $this->whatsappNumber();

        return $message !== '' ? $url . '?text=' . rawurlencode($message) : $url;
    }

    public function messengerUrl(string $ref = ''): string
    {
        $url = 'https://m.me/' . $this->messengerUsername();

        return $ref !== '' ? $url . '?ref=' . $ref : $url;
    }
}
