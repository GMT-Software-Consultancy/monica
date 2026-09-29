<?php

namespace App\Domains\Contact\ManageContact\Web\Controllers;

use App\Domains\Contact\ManageContact\Services\ExportContact;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ContactExportController extends Controller
{
    /**
     * Download everything Monica stores about the contact, as one JSON file.
     * The file is built in memory for this response only.
     */
    public function download(Request $request, Vault $vault, Contact $contact): Response
    {
        $export = (new ExportContact)->execute([
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vault->id,
            'contact_id' => $contact->id,
        ]);

        $content = json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return response($content, 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$this->filename($contact, $export['exported_at']).'"',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /**
     * The file name holds the contact's name, without characters that are
     * unsafe in file names, and the export date: `jane-doe-2026-09-29.json`.
     */
    private function filename(Contact $contact, string $exportedAt): string
    {
        $name = (string) Str::of($contact->name)->slug(language: App::getLocale());
        $date = Carbon::parse($exportedAt)->setTimezone(Auth::user()->timezone ?? 'UTC')->format('Y-m-d');

        return ($name !== '' ? $name : 'contact').'-'.$date.'.json';
    }
}
