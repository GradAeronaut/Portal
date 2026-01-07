<?php

/**
 * PublicId
 *
 * Единый модуль для работы с публичным 6-символьным ID на основе Crockford's Base32.
 *
 * Формат хранения: 6 символов, без пробелов.
 * Формат отображения (рекомендуемый в UI): "XXX YYY".
 */
class PublicId
{
    private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ'; // Crockford's Base32 (без I, L, O, U)
    private const LENGTH = 6;
    private const MAX_ATTEMPTS = 20;

    /**
     * Генерирует случайный, уникальный PublicID и проверяет его уникальность в users.public_id.
     *
     * @param \PDO $pdo
     * @return string
     *
     * @throws \RuntimeException Если не удалось подобрать уникальное значение за MAX_ATTEMPTS.
     */
    public static function generate(\PDO $pdo): string
    {
        $attempt = 0;
        $exists  = false;

        do {
            $candidate = self::randomId();

            $stmt = $pdo->prepare('SELECT id FROM users WHERE public_id = ? LIMIT 1');
            $stmt->execute([$candidate]);
            $exists = $stmt->fetchColumn() !== false;

            $attempt++;
        } while ($exists && $attempt < self::MAX_ATTEMPTS);

        if ($exists) {
            throw new \RuntimeException('unable_to_generate_unique_public_id');
        }

        return $candidate;
    }

    /**
     * Чистая генерация ID длиной 6 символов из алфавита Crockford's Base32.
     * Без проверки уникальности (полезно для тестов).
     */
    public static function randomId(): string
    {
        $alphabet = self::ALPHABET;
        $maxIndex = strlen($alphabet) - 1;

        $result = '';
        for ($i = 0; $i < self::LENGTH; $i++) {
            $idx = random_int(0, $maxIndex);
            $result .= $alphabet[$idx];
        }

        return $result;
    }

    /**
     * Форматирует ID для отображения в UI (ABC123 → ABC 123).
     * В БД и API рекомендуется хранить без пробелов.
     */
    public static function formatDisplay(string $publicId): string
    {
        $publicId = strtoupper(trim($publicId));

        if (strlen($publicId) !== self::LENGTH) {
            return $publicId;
        }

        return substr($publicId, 0, 3) . ' ' . substr($publicId, 3);
    }
}

