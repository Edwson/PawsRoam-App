<?php
// Create this exact function in includes/translation.php
function __($key, $params = [], $lang = null) {
    global $current_language;
    $lang = $lang ?? $current_language ?? 'en';

    // Load language file if not cached
    if (!isset($GLOBALS['translations'][$lang])) {
        $lang_file = __DIR__ . "/../lang/{$lang}/common.php";
        if (file_exists($lang_file)) {
            $GLOBALS['translations'][$lang] = include $lang_file;
        } else {
            // Fallback to English if the specified language file doesn't exist
            $fallback_lang_file = __DIR__ . "/../lang/en/common.php";
            if (file_exists($fallback_lang_file)) {
                $GLOBALS['translations'][$lang] = include $fallback_lang_file;
            } else {
                // If English also doesn't exist, return the key itself
                $GLOBALS['translations'][$lang] = [];
            }
        }
    }

    $translation = $GLOBALS['translations'][$lang][$key] ?? $key;

    // Replace parameters
    if (is_string($translation)) {
        foreach ($params as $param => $value) {
            $translation = str_replace("{{$param}}", $value, $translation);
        }
    }

    return $translation;
}
