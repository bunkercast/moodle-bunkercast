<?php
// Thin client for the Bunkercast Authenticated Access API.

namespace filter_bunkercast;

defined('MOODLE_INTERNAL') || die();

/**
 * Talks to Bunkercast. Server-side only: the API key must never reach a browser.
 */
class api {

    /** Bunkercast rejects a ttlSec outside this range with 400 invalid_ttl. */
    const TTL_MIN = 60;
    const TTL_MAX = 14400;

    /**
     * Mint a playback URL for one video and one viewer.
     *
     * @param string $fileid Bunkercast file id (uuid).
     * @param string $viewerref Opaque viewer reference. Bunkercast accepts
     *        A-Z a-z 0-9 _ . : - up to 64 chars, so a Moodle user id is fine
     *        and an email address would be rejected — never pass one.
     * @return array{url: string, expires: int}
     * @throws \moodle_exception
     */
    public static function mint(string $fileid, string $viewerref): array {
        $cfg = get_config('filter_bunkercast');

        $apikey = trim($cfg->apikey ?? '');
        if ($apikey === '') {
            throw new \moodle_exception('notconfigured', 'filter_bunkercast');
        }

        $base = rtrim($cfg->playerbase ?: 'https://player.bunkercast.com', '/');

        $ttl = (int)($cfg->ttlsec ?? 3600);
        $ttl = max(self::TTL_MIN, min(self::TTL_MAX, $ttl));

        $payload = [
            'fileId'    => $fileid,
            'ttlSec'    => $ttl,
            'viewerRef' => $viewerref,
        ];

        // Host lock is off by default: it depends on the browser sending a
        // referrer, and the Moodle mobile app generally does not.
        if (!empty($cfg->hostlock)) {
            $host = parse_url((string)(new \moodle_url('/'))->out(false), PHP_URL_HOST);
            if ($host) {
                $payload['hosts'] = [$host];
            }
        }

        $curl = new \curl();
        $curl->setHeader([
            'X-API-Key: ' . $apikey,
            'Content-Type: application/json',
            'Accept: application/json',
        ]);

        $response = $curl->post($base . '/api/playback-token', json_encode($payload), [
            'CURLOPT_TIMEOUT'        => 10,
            'CURLOPT_CONNECTTIMEOUT' => 5,
        ]);

        $status = (int)($curl->get_info()['http_code'] ?? 0);
        $data = json_decode((string)$response, true);

        if ($status !== 200 || !is_array($data) || empty($data['iframeSrc'])) {
            // Log the machine-readable reason for the administrator; show the
            // viewer nothing but "unavailable". Bunkercast error codes are
            // documented at <player base>/docs/api.
            $code = is_array($data) && isset($data['error']) ? $data['error'] : 'http_' . $status;
            debugging("filter_bunkercast: mint failed for {$fileid} ({$code})", DEBUG_DEVELOPER);
            throw new \moodle_exception('unavailable', 'filter_bunkercast');
        }

        // Trust our own ttl rather than parsing expiresAt, and expire a little
        // early so a cached URL is never handed out on the edge of validity.
        return [
            'url'     => (string)$data['iframeSrc'],
            'expires' => time() + max(30, $ttl - 30),
        ];
    }

    /**
     * List the account's ready videos, for the authoring picker.
     *
     * @return array<int, array{fileid: string, name: string, durationminutes: float}>
     * @throws \moodle_exception
     */
    public static function list_videos(): array {
        $cfg = get_config('filter_bunkercast');

        $apikey = trim($cfg->apikey ?? '');
        if ($apikey === '') {
            throw new \moodle_exception('notconfigured', 'filter_bunkercast');
        }

        $base = rtrim($cfg->playerbase ?: 'https://player.bunkercast.com', '/');

        $curl = new \curl();
        $curl->setHeader([
            'X-API-Key: ' . $apikey,
            'Accept: application/json',
        ]);

        // Only 'ready' videos can be played, so offering anything else in the
        // picker would just let a teacher embed a placeholder that fails.
        $response = $curl->get($base . '/api/videos', ['status' => 'ready', 'limit' => 500], [
            'CURLOPT_TIMEOUT'        => 10,
            'CURLOPT_CONNECTTIMEOUT' => 5,
        ]);

        $status = (int)($curl->get_info()['http_code'] ?? 0);
        $data = json_decode((string)$response, true);

        if ($status !== 200 || !is_array($data) || !isset($data['videos'])) {
            $code = is_array($data) && isset($data['error']) ? $data['error'] : 'http_' . $status;
            debugging("filter_bunkercast: list failed ({$code})", DEBUG_DEVELOPER);
            throw new \moodle_exception('listfailed', 'filter_bunkercast');
        }

        $out = [];
        foreach ($data['videos'] as $v) {
            if (empty($v['fileId'])) {
                continue;
            }
            $out[] = [
                'fileid'          => (string)$v['fileId'],
                'name'            => (string)($v['name'] ?? $v['fileId']),
                'durationminutes' => (float)($v['durationMinutes'] ?? 0),
            ];
        }

        return $out;
    }
}
