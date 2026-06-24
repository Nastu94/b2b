<?php

namespace App\Services;

class ConversationModerationService
{
    /**
     * Analizza il testo per trovare contatti o link esterni e restituisce il testo filtrato, lo stato e i flag.
     *
     * @param string $text
     * @return array
     */
    public function moderate(string $text): array
    {
        $filteredText = $text;
        $flags = [];

        // Definizione dei pattern
        $patterns = [
            'email' => '/[\w\.-]+@[\w\.-]+\.\w{2,}/i',
            'phone' => '/(?:\+?\d{1,3}[-.\s]?)?(?:\(?\d{2,4}\)?[-.\s]?)?\d{3,4}[-.\s]?\d{3,4}/',
            'url'   => '/(?:https?:\/\/)?(?:www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b(?:[-a-zA-Z0-9()@:%_\+.~#?&\/=]*)/i',
            'whatsapp' => '/(?i)(whatsapp|wa\.me)/',
            'telegram' => '/(?i)(telegram|t\.me)/',
            'instagram' => '/(?i)(instagram|\b(ig|insta)\b)/',
            'facebook' => '/(?i)(facebook|\bfb\b)/',
            'tiktok' => '/(?i)(tiktok|\btt\b)/',
            'email_word' => '/(?i)\b(email|e-mail|mail|chiocciola)\b/',
            'phone_word' => '/(?i)\b(numero|cellulare|telefono|cell)\b/'
        ];

        foreach ($patterns as $type => $pattern) {
            if (preg_match($pattern, $text)) {
                $flags[] = $type;
                $filteredText = preg_replace($pattern, '[contenuto rimosso]', $filteredText);
            }
        }

        $flags = array_unique($flags);
        $status = empty($flags) ? 'clean' : 'filtered';

        return [
            'original' => $text,
            'filtered' => $filteredText,
            'status'   => $status,
            'flags'    => $flags,
        ];
    }
}
