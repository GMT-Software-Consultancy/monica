<?php

namespace App\Domains\Contact\ManageContact\Web\Controllers;

use App\Domains\Contact\ManageContact\Services\ExportContact;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ContactFullExportController extends Controller
{
    /**
     * Download everything recorded about the contact, as one JSON file.
     *
     * The file is built in memory and only sent in this response: nothing
     * is stored on the server, and the response must not be cached.
     * If the export cannot be built completely, an error is returned and
     * no file is sent.
     */
    public function download(Request $request, Vault $vault, Contact $contact): JsonResponse
    {
        $export = app(ExportContact::class)->execute([
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vault->id,
            'contact_id' => $contact->id,
        ]);

        $content = json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $name = (string) Str::of($contact->name)->slug(language: App::getLocale());

        return response()->json([
            'data' => [
                'filename' => ($name !== '' ? $name : 'contact').'.json',
                'content' => $content,
            ],
        ], 200, [
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
