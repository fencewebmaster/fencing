<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers\Frontend;

use Fc\Admin\Services\AddressLookupService;

/**
 * Street suggestions for the Address fields (/address-lookup?q=12+hay+st&state=WA), from writable/au.jsonl.
 *
 * Session-less: called on every pause in typing. No `state` (nothing picked yet) falls back to the
 * state this site's visitors mostly live in. See AddressLookupService.
 */
final class AddressLookupController extends BaseFrontendController
{
    public function index(): void
    {
        $state = strtoupper(trim((string) $this->request->query('state', '')));
        if ($state === '') {
            $state = AddressLookupService::stateHintForHost((string) ($_SERVER['HTTP_HOST'] ?? ''));
        }

        $results = AddressLookupService::search((string) $this->request->query('q', ''), $state);

        header('Content-Type: application/json; charset=utf-8');
        // The same words always give the same streets until the data file is rebuilt.
        header('Cache-Control: public, max-age=3600');
        // available: false (no data file on this site) tells the field to stay quiet rather than say "no match" to every word.
        echo json_encode([
            'results'   => $results,
            'available' => is_file(AddressLookupService::dataPath()),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
