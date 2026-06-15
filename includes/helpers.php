<?php

function safe_photo_src(?string $path): ?string {
    if ($path === null || $path === '') {
        return null;
    }

    $norm = str_replace('\\', '/', ltrim($path, './'));
    if (strpos($norm, '..') !== false || strpos($norm, 'uploads/photos/') !== 0) {
        return null;
    }

    return file_exists($path) ? $path : null;
}

function status_badge_class(?string $status): string {
    $slug = strtolower((string) $status);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return 'badge badge-' . trim($slug, '-');
}

function initials(string $first, string $last = ''): string {
    $source = trim($first . ' ' . $last);
    if ($source === '') {
        return 'NA';
    }
    $parts = preg_split('/\s+/', $source);
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= strtoupper(substr($part, 0, 1));
    }
    return $letters ?: 'NA';
}
